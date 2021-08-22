<?php

namespace Minormous\Dali\Exceptions;

use Minormous\Dali\Exceptions\BaseException;

final class InvalidRepositoryException extends BaseException
{
    public static function sourceDoesNotExist(string $source): static
    {
        return new static("Source {$source} does not exist.", 500);
    }
}
