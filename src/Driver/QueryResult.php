<?php

namespace Minormous\Dali\Driver;

use Generator;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;
use Webmozart\Assert\Assert;

/**
 * @implements QueryResultInterface<non-empty-array<string,mixed>>
 */
final class QueryResult implements QueryResultInterface
{
    /**
     * @param Generator<int,array<string,mixed>,null,void> $iterator
     * @psalm-param Generator<int,non-empty-array<string,mixed>,null,void> $iterator
     */
    public function __construct(
        private int $count,
        private Generator $iterator
    ) {
    }

    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return array<int,array<string,mixed>>
     * @psalm-return array<int,non-empty-array<string,mixed>>
     */
    public function all(): array
    {
        $result = iterator_to_array($this->iterator);
        Assert::allNotEmpty($result);

        return $result;
    }

    /**
     * @return \Traversable<int,array<string,mixed>>&Generator<int,array<string,mixed>,null,void>
     * @psalm-return \Traversable<int,non-empty-array<string,mixed>>&Generator<int,non-empty-array<string,mixed>,null,void>
     */
    public function getIterator(): Generator
    {
        return $this->iterator;
    }
}
