<?php

namespace Minormous\Dali\Driver\Interfaces;

use IteratorAggregate;

/**
 * @template TObj
 * @template-extends IteratorAggregate<int, TObj>
 */
interface QueryResultInterface extends IteratorAggregate
{
    public function count(): int;

    /**
     * @return array<TObj>
     */
    public function all(): array;
}
