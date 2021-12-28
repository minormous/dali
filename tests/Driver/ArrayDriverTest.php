<?php

namespace Tests\Dali\Driver;

use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\ArrayDriver;
use Minormous\Dali\Enums\DriverType;
use Minormous\Dali\Exceptions\InvalidDriverException;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class ArrayDriverTest extends TestCase
{
    private ArrayDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $config = new DriverConfig('test', DriverType::ARRAY);
        $logger = new TestLogger();
        $driver = new ArrayDriver($config, $logger);
        $driver->addTable('test', [
            ['id' => 1, 'test' => 'something'],
            ['id' => 2, 'test' => 'somethingElse'],
            ['id' => 3, 'test' => 'something'],
            ['id' => 4, 'test' => 'another'],
            ['id' => 5, 'test' => '🤷‍♀️'],
        ]);

        $this->driver = $driver;
    }


    public function testFindOne()
    {
        $row = $this->driver->findOne('test', ['id' => 1]);

        $this->assertEquals('something', $row['test']);
    }

    public function testFind()
    {
        $rows = $this->driver->find('test', ['test' => 'something'], ['sort' => ['id', 'DESC']]);
        $rows = $rows->all();
        $this->assertEquals(3, $rows[0]['id']);
        $this->assertEquals(1, $rows[1]['id']);
    }

    public function testInsert()
    {
        $this->driver->insert('test', ['test' => 'new!']);
        $row = $this->driver->findOne('test', ['test' => 'new!']);

        $this->assertEquals(6, $row['id']);
    }

    public function testUnableToInsertExistingId()
    {
        $id = $this->driver->insert('test', ['id' => 1]);
        $this->assertNotEquals(1, $id);
        $this->assertEquals(6, $id);
    }

    public function testUpdate()
    {
        $count = $this->driver->update('test', ['id' => 1], ['test' => 'new!', 'somethingElse' => 'new!']);
        $this->assertEquals(1, $count);
        $row = $this->driver->findOne('test', ['id' => 1]);
        $this->assertEquals('new!', $row['test']);
        $this->assertEquals('new!', $row['somethingElse']);
    }

    public function testUnableToUpdateId()
    {
        $count = $this->driver->update('test', ['id' => 1], ['id' => 20]);
        $this->assertEquals(1, $count);
        $row = $this->driver->findOne('test', ['id' => 1]);
        $this->assertNotEmpty($row);
        $row = $this->driver->findOne('test', ['id' => 20]);
        $this->assertEmpty($row);
    }

    public function testDelete()
    {
        $count = $this->driver->delete('test', ['id' => 1]);
        $this->assertEquals(1, $count);
        $row = $this->driver->findOne('test', ['id' => 1]);
        $this->assertEmpty($row);
    }
}
