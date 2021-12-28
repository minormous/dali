<?php

namespace Minormous\Dali\Enums;

use Minormous\Dali\Driver\ArrayDriver;

enum DriverType
{
    case ARRAY;
    case MYSQL;
    case SQLITE;
    case DYNAMODB;
    case CUSTOM;

    public function driver(): string
    {
        return match($this) {
            self::ARRAY => ArrayDriver::class,
            self::MYSQL => 'MysqlDriver',
            self::SQLITE => 'SqliteDriver',
            self::DYNAMODB => 'DynamodbDriver',
            default => throw new \Exception('Unexpected driver type'),
        };
    }
}
