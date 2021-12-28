<?php

namespace Tests\Dali\Repository;

use Auryn\Injector;
use Minormous\Dali\Driver\ArrayDriver;
use Minormous\Dali\Enums\DriverType;
use Minormous\Dali\Repository\RepositoryResult;
use Psr\Log\Test\TestLogger;
use PHPUnit\Framework\TestCase;
use Minormous\Dali\RepositoryManager;
use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Repository\AbstractRepository;
use Minormous\Metabolize\Dali\MetadataReader;
use Tests\Dali\Assets\Entity;

class AbstractRepositoryTest extends TestCase
{
    protected function getRepo(): AbstractRepository
    {
        $manager = new RepositoryManager(
            new MetadataReader(),
            new TestLogger(),
            new Injector(),
            ['test' => new DriverConfig('array', DriverType::ARRAY)],
        );
        $repo = $manager->make(Entity::class);
        /** @var ArrayDriver $driver */
        $driver = $repo->getDriver();
        $driver->addTable('test', [
            ['id' => 1, 'something' => 'test1'],
            ['id' => 2, 'something' => 'test2'],
            ['id' => 3, 'something' => 'test3'],
            ['id' => 4, 'something' => 'test4'],
        ]);

        return $repo;
    }

    public function testFindById()
    {
        $repo = $this->getRepo();
        /** @var Entity|null $obj */
        $obj = $repo->findById(1);
        $this->assertInstanceOf(Entity::class, $obj);
        $this->assertEquals('test1', $obj->getSomething());

        $obj2 = $repo->findById(10);
        $this->assertNull($obj2);
    }

    public function testFindOne()
    {
        $repo = $this->getRepo();
        /** @var Entity|null $obj */
        $obj = $repo->findOne(['something' => 'test2']);
        $this->assertInstanceOf(Entity::class, $obj);
        $this->assertEquals(2, $obj->getId());

        /** @var Entity|null $obj */
        $obj2 = $repo->findOne(['something' => 'not-real']);
        $this->assertNull($obj2);
    }

    public function testFind()
    {
        $repo = $this->getRepo();
        /** @var RepositoryResult<Entity> */
        $result = $repo->find(['id' => ['>', 1]]);
        $this->assertEquals(3, $result->count());
        foreach ($result as $value) {
            $this->assertGreaterThan(1, $value->getId());
        }
    }

    public function testInsert()
    {
        $repo = $this->getRepo();

        /** @var Entity $entity */
        $entity = $repo->insert(new Entity([
            'something' => 'test-something',
        ]));

        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertEquals(5, $entity->getId());

        /** @var Entity $entity */
        $entity = $repo->findById(5);
        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertEquals(5, $entity->getId());
        $this->assertEquals('test-something', $entity->getSomething());
    }

    public function testUpdate()
    {
        $repo = $this->getRepo();
        /** @var Entity $entity */
        $entity = $repo->findById(1);
        $entity = $entity->with(something: 'something-new');
        /** @var Entity $entity */
        $entity = $repo->update($entity);
        $this->assertEquals(1, $entity->getId());
        $this->assertEquals('something-new', $entity->getSomething());

        /** @var Entity $entity */
        $entity = $repo->findById(1);
        $this->assertEquals(1, $entity->getId());
        $this->assertEquals('something-new', $entity->getSomething());
    }

    public function testDelete()
    {
        $repo = $this->getRepo();
        $entity = $repo->findById(1);
        $success = $repo->delete($entity);
        $this->assertTrue($success);

        $entity = $repo->findById(1);
        $this->assertNull($entity);
    }

    public function testDeleteWhere()
    {
        $repo = $this->getRepo();
        $success = $repo->deleteWhere(['something' => 'test1']);
        $this->assertTrue($success);

        $entity = $repo->findById(1);
        $this->assertNull($entity);
    }
}
