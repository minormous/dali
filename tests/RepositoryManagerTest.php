<?php

namespace Tests\Dali;

use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\ArrayDriver;
use Minormous\Dali\RepositoryManager;
use Minormous\Metabolize\Dali\MetadataReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

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
}
