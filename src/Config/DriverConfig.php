<?php

namespace Minormous\Dali\Config;

final class DriverConfig
{
    public function __construct(
        private string $sourceIdentifier = '',
        private string $driverType = '',
        private string $server = '',
        private int $port = 0,
        private string $database = '',
        private string $username = '',
        private string $password = '',
        private string $region = '',
        private string $roleArn = '',
        private bool $debugLoggingEnabled = false,
    ) {
    }

    public function getRoleArn(): string
    {
        return $this->roleArn;
    }

    public function getSourceIdentifier(): string
    {
        return $this->sourceIdentifier;
    }

    public function getDriverType(): string
    {
        return $this->driverType;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function isDebugLoggingEnabled(): bool
    {
        return $this->debugLoggingEnabled;
    }
}
