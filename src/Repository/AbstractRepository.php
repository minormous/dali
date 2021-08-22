<?php

namespace Minormous\Dali\Repository;

use Auryn\Injector;
use Doctrine\DBAL\Query\QueryBuilder;
use Generator;
use Minormous\Metabolize\Dali\Metadata;
use Minormous\Dali\Driver\AbstractDriver;
use Minormous\Dali\Driver\QueryResultInterface;
use Minormous\Dali\Entity\Interfaces\CastInterface;
use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Webmozart\Assert\Assert;

/**
 * @template TObj of EntityInterface
 */
abstract class AbstractRepository
{
    final public function __construct(
        protected AbstractDriver $driver,
        protected Metadata $metadata,
        protected Injector $container,
    ) {
    }

    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }

    /**
     * @param string|int $id
     * @return TObj|null
     */
    public function findById(string|int $id): ?EntityInterface
    {
        $idMetadata = $this->metadata->getIdColumn();
        $value = $this->driver->findOne($this->metadata->getTable(), [
            $idMetadata->getName() => $this->convertValueForDriver($idMetadata->getProperty(), $id),
        ]);

        if ($value === null) {
            return null;
        }

        return $this->buildEntity($value);
    }

    /**
     * @param array<string,mixed> $where
     * @return RepositoryResult
     */
    public function find(array $where, array $options = []): RepositoryResult
    {
        $columnWhere = [];
        foreach ($where as $key => $value) {
            [$key, $value] = $this->convertValueForDriver($key, $value);
            $columnWhere[$key] = $value;
        }
        $value = $this->driver->find($this->metadata->getTable(), $columnWhere);

        return new RepositoryResult($value->count(), $this->buildGenerator($value->getIterator()));
    }

    /**
     * @return TObj|null
     */
    public function findOne(array $where): ?EntityInterface
    {
        $columnWhere = [];
        foreach ($where as $key => $value) {
            [$key, $value] = $this->convertValueForDriver($key, $value);
            $columnWhere[$key] = $value;
        }
        $value = $this->driver->findOne($this->metadata->getTable(), $columnWhere);

        if ($value === null) {
            return null;
        }

        return $this->buildEntity($value);
    }

    /**
     * @param TObj $update
     * @return TObj|null
     */
    public function insert(EntityInterface $entity): ?EntityInterface
    {
        Assert::isInstanceOf($entity, $this->metadata->getClass());

        $id = $this->driver->insert($this->metadata->getTable(), $this->convertEntityForDriver($entity));

        $result = null;
        if ($id === 0) {
            $result = null;
        } elseif ($id) {
            $result = $this->findById($id);
        }

        if (!isset($result)) {
            return null;
        }

        return $result;
    }

    /**
     * @param TObj $update
     * @return bool
     */
    abstract public function update(EntityInterface $update): bool;

    abstract public function delete(array $where): int;

    abstract public function findFromQueryBuilder(QueryBuilder $queryBuilder): QueryResultInterface;
    abstract public function deleteFromQueryBuilder(QueryBuilder $queryBuilder);

    protected function buildEntity(array $values): EntityInterface
    {
        foreach ($values as $key => &$value) {
            $columnMetadata = $this->metadata->getColumn($key);
            if ($columnMetadata->getCastClass() instanceof CastInterface) {
                /** @var CastInterface $cast */
                $cast = $this->container->make($columnMetadata->getCastClass());
                $value = $cast->toValue($value);
            }
        }

        $class = $this->metadata->getClass();

        $entity = new $class($values);

        Assert::isInstanceOf($entity, EntityInterface::class);

        return $entity;
    }

    protected function convertValueForDriver(string $key, mixed $value): mixed
    {
        $columnMetadata = $this->metadata->getColumn($key);
        if ($columnMetadata->getCastClass() instanceof CastInterface) {
            /** @var CastInterface $cast */
            $cast = $this->container->make($columnMetadata->getCastClass());
            $value = $cast->toValue($value);
        }

        $value = $this->driver->convertValueForDriver($columnMetadata->getType(), $value);
        $key = $columnMetadata->getName();

        return [$key, $value];
    }

    protected function convertEntityForDriver(EntityInterface $entity): array
    {
        $arr = [];
        foreach ($entity->toArray() as $key => $value) {
            [$key, $value] = $this->convertValueForDriver($key, $value);
            $arr[$key] = $value;
        }

        return $arr;
    }

    protected function buildGenerator(Generator $iterable): Generator
    {
        while ($values = $iterable->current()) {
            yield $this->buildEntity($values);
            $iterable->next();
        }
    }
}
