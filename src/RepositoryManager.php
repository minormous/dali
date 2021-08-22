<?php

namespace Minormous\Dali;

use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\AbstractDriver;
use Minormous\Metabolize\Dali\MetadataReader;
use Minormous\Dali\Repository\AbstractRepository;
use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Minormous\Dali\Exceptions\InvalidRepositoryException;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class RepositoryManager
{
    /**
     * @template T of AbstractDriver
     * @var array<class-string,AbstractDriver>
     * @psalm-var array<class-string<T>,T>
     * @phpstan-var array<class-string<T>,T>
     */
    private array $drivers;

    /**
     * @param array<string,DriverConfig> $driverConfigs
     */
    public function __construct(
        private MetadataReader $metadataReader,
        private LoggerInterface $logger,
        private array $driverConfigs = [],
    ) {
    }

    public function getDriverForSource(string $source): AbstractDriver
    {
        if (!array_key_exists($source, $this->driverConfigs)) {
            throw InvalidRepositoryException::sourceDoesNotExist($source);
        }
        $config = $this->driverConfigs[$source];
        $driverClass = $config->getDriverClass();
        if (!array_key_exists($driverClass, $this->drivers)) {
            $driver = new $driverClass($config, $this->logger);
            Assert::isInstanceOf($driver, AbstractDriver::class);
            $this->drivers[$driverClass] = $driver;
        }

        return $this->drivers[$driverClass];
    }

    /**
     * @template TObj of EntityInterface
     * @param class-string<TObj> $class
     * @return AbstractRepository<TObj>
     */
    public function make(string $class): ?AbstractRepository
    {
        $metadata = $this->metadataReader->read($class);

        $repositoryClass = $metadata->getRepositoryClass();

        $repository = new $repositoryClass(
            $this->getDriverForSource($metadata->getSource()),
            $metadata,
        );

        Assert::isInstanceOf($repository, AbstractRepository::class);

        return null;
    }
}
