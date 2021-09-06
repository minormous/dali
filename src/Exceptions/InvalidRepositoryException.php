<?php

namespace Minormous\Dali\Exceptions;

use Minormous\Dali\Exceptions\BaseException;

final class InvalidRepositoryException extends BaseException
{
    public static function notImplemented(string $class, string $method): static
    {
        return new self("Repository method {$class}::{$method} not implemented.");
    }

    public static function noDeleteAll(): static
    {
        return new self('Unable to delete all from a table.');
    }
}
