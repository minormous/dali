<?php

namespace Tests\Dali\Assets;

use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Doctrine\DBAL\Query\QueryBuilder;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;
use Minormous\Dali\Exceptions\InvalidRepositoryException;
use Minormous\Dali\Repository\AbstractRepository;

class ArrayRepository extends AbstractRepository
{
    public function findFromQueryBuilder(QueryBuilder $queryBuilder): QueryResultInterface
    {
        throw InvalidRepositoryException::notImplemented(__CLASS__, __METHOD__);
    }

    public function deleteFromQueryBuilder(QueryBuilder $queryBuilder): bool
    {
        throw InvalidRepositoryException::notImplemented(__CLASS__, __METHOD__);
    }
}
