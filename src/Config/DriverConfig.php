<?php

namespace Minormous\Dali\Config;

use Minormous\Dali\Enums\DriverType;

final class DriverConfig
{
    public function __construct(
        private string $sourceIdentifier,
        private DriverType $driverType = DriverType::CUSTOM,
        private array $config = [],
        private bool $debugLoggingEnabled = false,
        private string $driverClass = '',
    ) {
    }

    public function getSourceIdentifier(): string
    {
        return $this->sourceIdentifier;
    }

    public function getDriverType(): DriverType
    {
        return $this->driverType;
    }

    public function isDebugLoggingEnabled(): bool
    {
        return $this->debugLoggingEnabled;
    }

    public function getDriverClass(): string
    {
        return $this->driverClass;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
