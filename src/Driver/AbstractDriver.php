<?php

namespace Minormous\Dali\Driver;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Query\QueryBuilder;
use IteratorAggregate;
use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;

abstract class AbstractDriver implements IteratorAggregate
{
    protected DriverConfig $driverConfig;
    protected LoggerInterface $logger;

    final public function __construct(DriverConfig $driverConfig, LoggerInterface $logger)
    {
        $this->driverConfig = $driverConfig;
        $this->logger = $logger;
        $this->setup($driverConfig);
    }

    abstract protected function setup(DriverConfig $driverConfig): void;

    public function logQueries(): void
    {
        // extend where appropriate
    }

    /**
     * @param array<string,mixed|array{string,mixed}> $where
     * @param array{offset?:int,sort?:array{string,string},limit?:int} $options
     * @psalm-param array{offset?:positive-int,sort?:array{string,string},limit?:positive-int} $options
     * @return QueryResultInterface<array<string,mixed>>
     * @psalm-return QueryResultInterface<non-empty-array<string,mixed>>
     */
    abstract public function find(string $table, array $where, array $options = []): QueryResultInterface;
    /**
     * @param array<string,scalar|null|array{0:string,1?:null|scalar}>  $where
     * @psalm-return null|non-empty-array<string,mixed>
     */
    abstract public function findOne(string $table, array $where): ?array;
    /**
     * @param array<string,mixed> $data
     * @psalm-param non-empty-array<string,mixed> $data
     */
    abstract public function insert(string $table, array $data): string|int;

    /**
     * @param array<string,scalar|null|array{0:string,1?:null|scalar}> $where
     * @param array<string,mixed> $data
     * @psalm-param non-empty-array<string,mixed> $data
     */
    abstract public function update(string $table, array $where, array $data): int;

    /**
     * @param array<string,scalar|null|array{0:string,1?:null|scalar}> $where
     */
    abstract public function delete(string $table, array $where): int;

    abstract public function findFromQueryBuilder(QueryBuilder $queryBuilder): QueryResultInterface;
    abstract public function deleteFromQueryBuilder(QueryBuilder $queryBuilder): int;

    /**
     * @param mixed $value
     * @return scalar|null
     */
    abstract public function convertValueForDriver(string $type, mixed $value): mixed;
}
