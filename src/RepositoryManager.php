<?php

namespace Minormous\Dali;

use Auryn\Injector;
use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\AbstractDriver;
use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Minormous\Dali\Enums\DriverType;
use Minormous\Metabolize\Dali\MetadataReader;
use Minormous\Dali\Repository\AbstractRepository;
use Minormous\Dali\Exceptions\InvalidDriverException;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class RepositoryManager
{
    /** @var array<string,AbstractDriver> $drivers */
    private array $drivers = [];

    /**
     * @param array<string,DriverConfig> $driverConfigs
     */
    public function __construct(
        private MetadataReader $metadataReader,
        private LoggerInterface $logger,
        private Injector $container,
        private array $driverConfigs = [],
    ) {
    }

    public function getDriverForSource(string $source): AbstractDriver
    {
        if (!array_key_exists($source, $this->driverConfigs)) {
            throw InvalidDriverException::sourceDoesNotExist($source);
        }
        $config = $this->driverConfigs[$source];
        $driverType = $config->getDriverType();

        if (!array_key_exists($source, $this->drivers)) {
            $driverClass = $driverType === DriverType::CUSTOM ?
                $config->getDriverClass() :
                $driverType->driver();

            $driver = $this->container->make(
                $driverClass,
                [
                    ':driverConfig' => $config,
                    ':logger' => $this->logger,
                ]
            );

            $this->drivers[$source] = $driver;
        }

        return $this->drivers[$source];
    }

    /**
     * @param class-string<EntityInterface> $class
     * @return AbstractRepository
     */
    public function make(string $class): AbstractRepository
    {
        $metadata = $this->metadataReader->read($class);

        $repositoryClass = $metadata->getRepositoryClass();

        $repository = new $repositoryClass(
            $this->getDriverForSource($metadata->getSource()),
            $metadata,
            $this->container,
        );

        Assert::isInstanceOf($repository, AbstractRepository::class);

        return $repository;
    }
}
