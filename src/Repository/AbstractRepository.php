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
use Traversable;
use Webmozart\Assert\Assert;

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
     * @param array{offset?:int,sort?:array{string,string},limit?:int} $options
     * @psalm-param array{offset?:positive-int,sort?:array{string,string},limit?:positive-int} $options
     */
    public function find(array $where, array $options = []): RepositoryResult
    {
        $columnWhere = $this->convertWhereForDriver($where);
        $value = $this->driver->find($this->metadata->getTable(), $columnWhere, $options);

        return new RepositoryResult($value->count(), $this->buildGenerator($value->getIterator()));
    }

    /**
     * @param array<string,mixed|array{string,mixed}> $where
     * @return EntityInterface|null
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

        $entity = $this->findById($id);
        Assert::notNull($entity);

        return $entity;
    }

    public function delete(EntityInterface $entity): bool
    {
        Assert::isInstanceOf($entity, $this->metadata->getClass());
        $where = $this->entityToWhere($entity);

        $count = $this->driver->delete($this->metadata->getTable(), $where);

        return $count > 0;
    }

    /**
     * @param array<string,mixed|array{string,mixed}> $where
     * @return boolean
     */
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
    abstract public function deleteFromQueryBuilder(QueryBuilder $queryBuilder): bool;

    protected function getEntityId(EntityInterface $entity): string|int
    {
        $idColumn = $this->metadata->getIdColumn();

        if (!$idColumn) {
            throw InvalidEntityException::noIdColumn($this->metadata->getClass());
        }

        /** @var string|int|null $id */
        $id = $entity->toArray()[$idColumn->getProperty()] ?? null;

        if ($id === null) {
            throw EntityDoesNotExistException::withUnsetId($this->metadata->getClass());
        }

        return $id;
    }

    /**
     * @return array<string,scalar|null|array{string,scalar|null}>
     */
    protected function entityToWhere(EntityInterface $entity): array
    {
        $idMetadata = $this->metadata->getIdColumn();
        if (!$idMetadata) {
            throw InvalidEntityException::noIdColumn($this->metadata->getClass());
        }

        [$key, $value] = $this->convertValueForDriver($idMetadata->getProperty(), $this->getEntityId($entity));

        return [$key => $value];
    }

    /**
     * @template T of non-empty-array<string,mixed>|null
     * @param array<string,mixed>|null $values
     * @psalm-param T $values
     * @return EntityInterface|null
     * @psalm-return (T is null ? null : EntityInterface)
     */
    protected function buildEntity(?array $values): ?EntityInterface
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => &$value) {
            $columnMetadata = $this->metadata->getColumn($key);
            if (class_exists($columnMetadata->getCastClass())) {
                $cast = $this->container->make($columnMetadata->getCastClass());
                if (!($cast instanceof CastInterface)) {
                    continue;
                }
                $value = $cast->toValue($value);
            }
        }

        /** @var class-string<EntityInterface> $class */
        $class = $this->metadata->getClass();

        $entity = new $class($values);

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        Assert::isInstanceOf($entity, EntityInterface::class);

        return $entity;
    }

    /**
     * @param string $key
     * @param mixed|array{0:string,1?:mixed} $value
     * @return array{0:string,1:scalar|null|array{0:string,1:null|scalar}}
     */
    protected function convertValueForDriver(string $key, mixed $value): array
    {
        $operator = null;
        $columnMetadata = $this->metadata->getColumn($key);
        if (\is_array($value)) {
            /** @var string $operator */
            $operator = $value[0];
            $value = $value[1] ?? null;
        }
        if (class_exists($columnMetadata->getCastClass())) {
            $cast = $this->container->make($columnMetadata->getCastClass());
            if ($cast instanceof CastInterface) {
                $value = $cast->toValue($value);
            }
        }

        $value = $this->driver->convertValueForDriver($columnMetadata->getType(), $value);
        $key = $columnMetadata->getName();

        if ($operator !== null) {
            $value = [$operator, $value];
        }

        return [$key, $value];
    }

    /**
     * @psalm-return non-empty-array<string,mixed>
     */
    protected function convertEntityForDriver(EntityInterface $entity): array
    {
        /** @psalm-var non-empty-array<string,mixed> $arr */
        $arr = [];
        foreach ($entity->toArray() as $key => $value) {
            [$key, $value] = $this->convertValueForDriver($key, $value);
            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * @param array<string,mixed|array{string,mixed}> $where
     * @return array<string,scalar|null|array{0:string,1?:scalar|null}>
     */
    protected function convertWhereForDriver(array $where): array
    {
        $columnWhere = [];
        foreach ($where as $key => $value) {
            [$key, $value] = $this->convertValueForDriver($key, $value);
            $columnWhere[$key] = $value;
        }

        return $columnWhere;
    }

    /**
     * @param \Traversable<int,array<string,mixed>> $iterable
     * @psalm-param Traversable<int,non-empty-array<string,mixed>> $iterable
     * @return Generator<int,EntityInterface,mixed,void>
     */
    protected function buildGenerator(Traversable $iterable): Generator
    {
        foreach ($iterable as $key => $values) {
            $entity = $this->buildEntity($values);
            yield $key => $entity;
        }
    }
}
