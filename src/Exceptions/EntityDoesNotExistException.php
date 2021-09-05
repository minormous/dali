<?php

namespace Minormous\Dali\Exceptions;

use Minormous\Dali\Exceptions\BaseException;

final class EntityDoesNotExistException extends BaseException
{
    public static function fromId(string $class, $id)
    {
        return new self("Entity [{$class}] not found with {$id}");
    }

    public static function withUnsetId(string $class)
    {
        return new self("Entity [{$class}] has no ID set.");
    }
}
