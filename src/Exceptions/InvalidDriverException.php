<?php

namespace Minormous\Dali\Exceptions;

final class InvalidDriverException extends BaseException
{
    public static function fromDriverType(string $type): static
    {
        return new static("Driver Type [{$type}] does not exist.", 500);
    }

    public static function notImplemented(string $method, string $msg): static
    {
        return new static("Method [{$method}] not implemented for this driver: {$msg}", 500);
    }
    
    public static function sourceDoesNotExist(string $source): static
    {
        return new static("Source {$source} does not exist.", 500);
    }
}
