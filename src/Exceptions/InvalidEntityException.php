<?php

namespace Minormous\Dali\Exceptions;

use Minormous\Dali\Exceptions\BaseException;

final class InvalidEntityException extends BaseException
{
    public static function withProperty(string $class, string $key)
    {
        return new self("Property <{$class}::{$key}> does not exist.");
    }
}
