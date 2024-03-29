<?php

namespace Minormous\Dali\Exceptions;

use Minormous\Dali\Exceptions\BaseException;

final class InvalidEntityException extends BaseException
{
    public static function withProperty(string $class, string $key): static
    {
        return new self("Property <{$class}::{$key}> does not exist.");
    }

    public static function noIdColumn(string $class): static
    {
        return new self("Entity [{$class}] has no ID column defined. Unable to find entity.");
    }
}
