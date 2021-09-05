<?php

namespace Minormous\Dali\Exceptions;

use Minormous\Dali\Exceptions\BaseException;

final class InvalidRepositoryException extends BaseException
{
    public static function notImplemented(string $class, string $method)
    {
        return new self("Repository method {$class}::{$method} not implemented.");
    }

    public static function noDeleteAll()
    {
        return new self('Unable to delete all from a table.');
    }
}
