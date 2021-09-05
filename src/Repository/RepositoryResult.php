<?php

namespace Minormous\Dali\Repository;

use Generator;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;
use Minormous\Dali\Entity\Interfaces\EntityInterface;

final class RepositoryResult implements QueryResultInterface
{
    public function __construct(
        private int $count,
        private Generator $iterator,
    ) {
    }

    public function count(): int
    {
        return $this->count;
    }

    public function all(): array
    {
        return iterator_to_array($this->iterator);
    }

    public function getIterator()
    {
        return $this->iterator;
    }

    public function first(): EntityInterface
    {
        return $this->iterator->current();
    }

    public function last(): EntityInterface
    {
        return $this->all()[$this->count - 1];
    }
}
