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

    abstract protected function setup(DriverConfig $driverConfig);

    public function logQueries()
    {
        // extend where appropriate
    }

    abstract public function find(string $table, array $where, array $options = []): QueryResultInterface;
    abstract public function findOne(string $table, array $where): ?array;
    abstract public function insert(string $table, array $data): int;

    abstract public function update(string $table, array $where, array $data): int;

    abstract public function delete(string $table, array $where): int;

    abstract public function getConnection();

    abstract public function findFromQueryBuilder(QueryBuilder $queryBuilder): QueryResultInterface;
    abstract public function deleteFromQueryBuilder(QueryBuilder $queryBuilder);

    abstract public function convertValueForDriver(string $type, mixed $value): mixed;
}
