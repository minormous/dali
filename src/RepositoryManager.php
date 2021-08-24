<?php

namespace Minormous\Dali;

use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\AbstractDriver;
use Minormous\Dali\Driver\ArrayDriver;
use Minormous\Metabolize\Dali\MetadataReader;
use Minormous\Dali\Repository\AbstractRepository;
use Minormous\Dali\Entity\Interfaces\EntityInterface;
use Minormous\Dali\Exceptions\InvalidDriverException;
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
    private array $drivers = [];

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
            throw InvalidDriverException::sourceDoesNotExist($source);
        }
        $config = $this->driverConfigs[$source];
        $driverType = $config->getDriverType();
        if (!array_key_exists($driverType, $this->drivers)) {
            switch ($driverType) {
                case 'array':
                    $this->drivers[$driverType] = new ArrayDriver($config, $this->logger);
                    break;
                default:
                    if (!class_exists($driverType)) {
                        throw InvalidDriverException::fromDriverType($driverType);
                    }
                    $driver = new $driverType($config, $this->logger);
                    Assert::isInstanceOf($driver, AbstractDriver::class);
                    $this->drivers[$driverType] = $driver;
                    break;
            }
        }

        return $this->drivers[$driverType];
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
