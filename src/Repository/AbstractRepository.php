<?php

namespace Minormous\Dali\Repository;

use Auryn\Injector;
use Doctrine\DBAL\Query\QueryBuilder;
use Generator;
use Minormous\Metabolize\Dali\Metadata;
use Minormous\Dali\Driver\AbstractDriver;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;
use Minormous\Dali\Entity\Interfaces\CastInterface;
use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Minormous\Dali\Exceptions\EntityDoesNotExistException;
use Minormous\Dali\Exceptions\InvalidEntityException;
use Minormous\Dali\Exceptions\InvalidRepositoryException;
use ReflectionClass;
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
        if (!$idMetadata) {
            throw InvalidEntityException::noIdColumn($this->metadata->getClass());
        }
        [$key, $value] = $this->convertValueForDriver($idMetadata->getProperty(), $id);
        $value = $this->driver->findOne($this->metadata->getTable(), [$key => $value]);

        return $this->buildEntity($value);
    }

    /**
     * @param array<string,mixed> $where
     * @return RepositoryResult
     */
    public function find(array $where, array $options = []): RepositoryResult
    {
        $columnWhere = $this->convertWhereForDriver($where);
        $value = $this->driver->find($this->metadata->getTable(), $columnWhere, $options);

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
     * @param TObj $entity
     * @return TObj
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        Assert::isInstanceOf($entity, $this->metadata->getClass());

        $id = $this->getEntityId($entity);
        $existing = $this->findById($id);
        if ($existing === null) {
            throw EntityDoesNotExistException::fromId($this->metadata->getClass(), $id);
        }

        $where = $this->entityToWhere($entity);
        $data = $this->convertEntityForDriver($entity);

        $count = $this->driver->update($this->metadata->getTable(), $where, $data);

        if ($count === 0) {
            return $entity;
        }

        return $this->findById($id);
    }

    public function delete(EntityInterface $entity): bool
    {
        Assert::isInstanceOf($entity, $this->metadata->getClass());
        $where = $this->entityToWhere($entity);

        $count = $this->driver->delete($this->metadata->getTable(), $where);

        return $count > 0;
    }

    public function deleteWhere(array $where): bool
    {
        if (empty($where)) {
            throw InvalidRepositoryException::noDeleteAll();
        }

        $columnWhere = $this->convertWhereForDriver($where);
        $count = $this->driver->delete($this->metadata->getTable(), $columnWhere);

        return $count > 0;
    }

    abstract public function findFromQueryBuilder(QueryBuilder $queryBuilder): QueryResultInterface;
    abstract public function deleteFromQueryBuilder(QueryBuilder $queryBuilder);

    protected function getEntityId(EntityInterface $entity): string|int
    {
        $idColumn = $this->metadata->getIdColumn();

        if (!$idColumn) {
            throw InvalidEntityException::noIdColumn($this->metadata->getClass());
        }

        $id = $entity->toArray()[$idColumn->getProperty()] ?? null;

        if ($id === null) {
            throw EntityDoesNotExistException::withUnsetId($this->metadata->getClass());
        }

        return $id;
    }

    protected function entityToWhere(EntityInterface $entity): array
    {
        $idMetadata = $this->metadata->getIdColumn();
        if (!$idMetadata) {
            throw InvalidEntityException::noIdColumn($this->metadata->getClass());
        }

        [$key, $value] = $this->convertValueForDriver($idMetadata->getProperty(), $this->getEntityId($entity));

        return [$key => $value];
    }

    protected function buildEntity(?array $values): ?EntityInterface
    {
        if ($values === null) {
            return null;
        }

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
        $operator = null;
        $columnMetadata = $this->metadata->getColumn($key);
        if (\is_array($value)) {
            if (\count($value) > 1) {
                [$operator, $value] = $value;
            } else {
                $operator = [$value];
                $value = null;
            }
        }
        if (
            class_exists($columnMetadata->getCastClass()) &&
            $columnMetadata->getCastClass() instanceof CastInterface
        ) {
            /** @var CastInterface $cast */
            $cast = $this->container->make($columnMetadata->getCastClass());
            $value = $cast->toValue($value);
        }

        $value = $this->driver->convertValueForDriver($columnMetadata->getType(), $value);
        $key = $columnMetadata->getName();

        if ($operator !== null) {
            $value = [$operator, $value];
        }

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

    protected function convertWhereForDriver(array $where): array
    {
        $columnWhere = [];
        foreach ($where as $key => $value) {
            [$key, $value] = $this->convertValueForDriver($key, $value);
            $columnWhere[$key] = $value;
        }

        return $columnWhere;
    }

    protected function buildGenerator(Generator $iterable): Generator
    {
        while ($values = $iterable->current()) {
            yield $this->buildEntity($values);
            $iterable->next();
        }
    }
}
