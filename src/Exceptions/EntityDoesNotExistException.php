<?php

namespace Minormous\Dali\Exceptions;

use Minormous\Dali\Exceptions\BaseException;

final class EntityDoesNotExistException extends BaseException
{
    public static function fromId(string $class, string|int $id): static
    {
        return new self("Entity [{$class}] not found with {$id}");
    }

    public static function withUnsetId(string $class): static
    {
        return new self("Entity [{$class}] has no ID set.");
    }
}
