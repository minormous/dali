<?php

namespace Tests\Dali;

use Auryn\Injector;
use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\ArrayDriver;
use Minormous\Dali\RepositoryManager;
use Minormous\Metabolize\Dali\MetadataReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Tests\Dali\Assets\ArrayRepository;
use Tests\Dali\Assets\Entity;

class RepositoryManagerTest extends TestCase
{
    /**
     * @dataProvider driverDataProvider
     */
    public function testGetDriverForSource(string $type)
    {
        $manager = new RepositoryManager(
            new MetadataReader([]),
            new TestLogger(),
            new Injector(),
            ['test' => new DriverConfig('array', $type)],
        );

        $driver = $manager->getDriverForSource('test');
        $this->assertInstanceOf(ArrayDriver::class, $driver);

        $driver2 = $manager->getDriverForSource('test');

        $this->assertSame($driver, $driver2);
    }

    public function driverDataProvider(): array
    {
        return [
            'array' => ['array'],
            'array class' => [ArrayDriver::class],
        ];
    }

    public function testMake()
    {
        $manager = new RepositoryManager(
            new MetadataReader([]),
            new TestLogger(),
            new Injector(),
            ['test' => new DriverConfig('array', 'array')],
        );

        $repository = $manager->make(Entity::class);
        $this->assertInstanceOf(ArrayRepository::class, $repository);

        /** @var \Minormous\Dali\Driver\ArrayDriver $driver */
        $driver = $repository->getDriver();
        $driver->addTable('test', [
            ['id' => 1, 'something' => 'test'],
        ]);

        /** @var Entity $entity */
        $entity = $repository->findById(1);
        $this->assertEquals('test', $entity->getSomething());
    }
}
