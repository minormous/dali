<?php

namespace Minormous\Dali\Driver;

use Generator;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;

final class QueryResult implements QueryResultInterface
{
    public function __construct(
        private int $count,
        private Generator $iterator
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
}
