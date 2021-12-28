<?php

namespace Minormous\Dali\Repository;

use Generator;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;
use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Webmozart\Assert\Assert;

/**
 * @implements QueryResultInterface<EntityInterface>
 */
final class RepositoryResult implements QueryResultInterface
{
    /**
     * @param int $count
     * @param Generator<int,EntityInterface,mixed,void> $iterator
     */
    public function __construct(
        private int $count,
        private Generator $iterator,
    ) {
    }

    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return array<int,EntityInterface>
     */
    public function all(): array
    {
        $result = iterator_to_array($this->iterator);

        Assert::allIsInstanceOf($result, EntityInterface::class);
        Assert::isList($result);

        return $result;
    }

    /**
     * @return \Traversable<int,EntityInterface>&Generator<int,EntityInterface,null,EntityInterface>
     */
    public function getIterator(): Generator
    {
        return $this->iterator;
    }

    public function first(): ?EntityInterface
    {
        return $this->all()[0] ?? null;
    }

    public function last(): ?EntityInterface
    {
        return $this->all()[$this->count - 1] ?? null;
    }
}
