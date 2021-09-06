<?php

namespace Minormous\Dali\Driver\Interfaces;

use IteratorAggregate;

/**
 * @template TObj
 * @extends IteratorAggregate<int,TObj>
 */
interface QueryResultInterface extends IteratorAggregate
{
    public function count(): int;

    /**
     * @return array<int,TObj>
     */
    public function all(): array;
}
