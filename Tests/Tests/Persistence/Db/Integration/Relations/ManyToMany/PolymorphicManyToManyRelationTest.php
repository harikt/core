<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Relations\ManyToMany;

use Iddigital\Cms\Core\Persistence\Db\Mapping\IEntityMapper;
use Iddigital\Cms\Core\Persistence\Db\Mapping\IOrm;
use Iddigital\Cms\Core\Persistence\Db\Schema\Table;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\DbIntegrationTest;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\ManyToManyRelation\AnotherEntity;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\ManyToManyRelation\OneEntity;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\ManyToManyRelation\Polymorphic\AnotherEntitySubclass;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\ManyToManyRelation\Polymorphic\OneEntityMapper;
use Iddigital\Cms\Core\Tests\Persistence\Db\Mock\MockDatabase;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PolymorphicManyToManyRelationTest extends DbIntegrationTest
{
    /**
     * @var Table
     */
    protected $oneTable;

    /**
     * @var Table
     */
    protected $joinTable;

    /**
     * @var Table
     */
    protected $anotherTable;

    /**
     * @var Table
     */
    protected $anotherSubclassesTable;

    /**
     * @inheritDoc
     */
    protected function loadOrm()
    {
        return OneEntityMapper::orm();
    }

    /**
     * {@inheritDoc}
     */
    protected function buildDatabase(MockDatabase $db, IOrm $orm)
    {
        parent::buildDatabase($db, $orm);
        $db->createForeignKey('one_anothers.one_id', 'ones.id');
        $db->createForeignKey('one_anothers.another_id', 'anothers.id');
        $db->createForeignKey('another_subclasses.id', 'anothers.id');
        $this->oneTable               = $db->getTable('ones')->getStructure();
        $this->joinTable              = $db->getTable('one_anothers')->getStructure();
        $this->anotherTable           = $db->getTable('anothers')->getStructure();
        $this->anotherSubclassesTable = $db->getTable('another_subclasses')->getStructure();
    }

    public function testPersistNoChildren()
    {
        $entity = new OneEntity();

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
                'ones'               => [
                        ['id' => 1]
                ],
                'anothers'           => [],
                'another_subclasses' => [],
                'one_anothers'       => [],
        ]);
    }

    public function testPersistMultipleWithSharedChildren()
    {
        $another1 = new AnotherEntity(null, 1);
        $another2 = new AnotherEntitySubclass(null, 2, true);
        $another3 = new AnotherEntity(null, 3);
        $entities = [
                new OneEntity(null, [
                        $another1,
                        $another2,
                        $another3,
                ]),
                new OneEntity(null, [
                        $another1,
                        $another3,
                ]),
                new OneEntity(null, [
                        $another2,
                        $another3,
                ]),
        ];

        $this->repo->saveAll($entities);

        $this->assertDatabaseDataSameAs([
                'ones'               => [
                        ['id' => 1],
                        ['id' => 2],
                        ['id' => 3],
                ],
                'anothers'           => [
                        ['id' => 1, 'val' => 1],
                        ['id' => 2, 'val' => 2],
                        ['id' => 3, 'val' => 3],
                ],
                'another_subclasses' => [
                        ['id' => 2, 'data' => true],
                ],
                'one_anothers'       => [
                        ['one_id' => 1, 'another_id' => 1],
                        ['one_id' => 1, 'another_id' => 2],
                        ['one_id' => 1, 'another_id' => 3],
                        ['one_id' => 2, 'another_id' => 1],
                        ['one_id' => 2, 'another_id' => 3],
                        ['one_id' => 3, 'another_id' => 2],
                        ['one_id' => 3, 'another_id' => 3],
                ],
        ]);
    }

    public function testPersistExisting()
    {
        $this->db->setData([
                'ones'               => [
                        ['id' => 1],
                ],
                'anothers'           => [
                        ['id' => 1, 'val' => 1],
                        ['id' => 2, 'val' => 2],
                        ['id' => 3, 'val' => 3],
                ],
                'another_subclasses' => [
                        ['id' => 2, 'data' => true],
                ],
                'one_anothers'       => [
                        ['one_id' => 1, 'another_id' => 1],
                        ['one_id' => 1, 'another_id' => 2],
                        ['one_id' => 1, 'another_id' => 3],
                ],
        ]);

        $entity = new OneEntity(1, [
                new AnotherEntity(null, 1),
                new AnotherEntitySubclass(null, 2, false),
                new AnotherEntity(2, 3),
        ]);

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
                'ones'               => [
                        ['id' => 1],
                ],
                'anothers'           => [
                        ['id' => 1, 'val' => 1],
                        ['id' => 2, 'val' => 3],
                        ['id' => 3, 'val' => 3],
                        ['id' => 4, 'val' => 1],
                        ['id' => 5, 'val' => 2],
                ],
                'another_subclasses' => [
                        ['id' => 2, 'data' => true],
                        ['id' => 5, 'data' => false],
                ],
                'one_anothers'       => [
                        ['one_id' => 1, 'another_id' => 4],
                        ['one_id' => 1, 'another_id' => 5],
                        ['one_id' => 1, 'another_id' => 2],
                ],
        ]);
    }

    public function testLoadWithDuplicates()
    {
        $this->db->setData([
                'ones'               => [
                        ['id' => 1]
                ],
                'anothers'           => [
                        ['id' => 1, 'val' => 1],
                ],
                'another_subclasses' => [
                        ['id' => 1, 'data' => true],
                ],
                'one_anothers'       => [
                        ['one_id' => 1, 'another_id' => 1],
                        ['one_id' => 1, 'another_id' => 1],
                ],
        ]);

        /** @var OneEntity $actual */
        $actual = $this->repo->get(1);
        $this->assertEquals(new OneEntity(1, [
                $another = new AnotherEntitySubclass(1, 1, true),
                $another,
        ]), $actual);
        $this->assertSame($actual->others[0], $actual->others[1]);
    }

    /**
     * @return void
     */
    public function testLoadWithSharedChildren()
    {
        $this->db->setData([
                'ones'               => [
                        ['id' => 1],
                        ['id' => 2],
                        ['id' => 3],
                ],
                'anothers'           => [
                        ['id' => 1, 'val' => 1],
                        ['id' => 2, 'val' => 2],
                        ['id' => 3, 'val' => 3],
                ],
                'another_subclasses' => [
                        ['id' => 2, 'data' => true],
                ],
                'one_anothers'       => [
                        ['one_id' => 1, 'another_id' => 1],
                        ['one_id' => 1, 'another_id' => 2],
                        ['one_id' => 1, 'another_id' => 3],
                        ['one_id' => 2, 'another_id' => 1],
                        ['one_id' => 2, 'another_id' => 3],
                        ['one_id' => 3, 'another_id' => 2],
                        ['one_id' => 3, 'another_id' => 3],
                ],
        ]);

        $another1 = new AnotherEntity(1, 1);
        $another2 = new AnotherEntitySubclass(2, 2, true);
        $another3 = new AnotherEntity(3, 3);
        $entities = [
                new OneEntity(1, [
                        $another1,
                        $another2,
                        $another3,
                ]),
                new OneEntity(2, [
                        $another1,
                        $another3,
                ]),
                new OneEntity(3, [
                        $another2,
                        $another3,
                ]),
        ];

        /** @var OneEntity[] $actual */
        $actual = $this->repo->getAll();
        $this->assertEquals($entities, $actual);
        $this->assertSame($actual[0]->others[0], $actual[1]->others[0]);
    }

    /**
     * @return void
     */
    public function testRemove()
    {
        $this->db->setData([
                'ones'               => [
                        ['id' => 1],
                        ['id' => 2],
                        ['id' => 3],
                ],
                'anothers'           => [
                        ['id' => 1, 'val' => 1],
                        ['id' => 2, 'val' => 2],
                        ['id' => 3, 'val' => 3],
                ],
                'another_subclasses' => [
                        ['id' => 2, 'data' => true],
                ],
                'one_anothers'       => [
                        ['one_id' => 1, 'another_id' => 1],
                        ['one_id' => 1, 'another_id' => 2],
                        ['one_id' => 1, 'another_id' => 3],
                        ['one_id' => 2, 'another_id' => 1],
                        ['one_id' => 2, 'another_id' => 3],
                        ['one_id' => 3, 'another_id' => 2],
                        ['one_id' => 3, 'another_id' => 3],
                ],
        ]);

        /** @var OneEntity[] $actual */
        $this->repo->removeAllById([1, 3]);

        $this->assertDatabaseDataSameAs([
                'ones'               => [
                        ['id' => 2],
                ],
                'anothers'           => [
                        ['id' => 1, 'val' => 1],
                        ['id' => 2, 'val' => 2],
                        ['id' => 3, 'val' => 3],
                ],
                'another_subclasses' => [
                        ['id' => 2, 'data' => true],
                ],
                'one_anothers'       => [
                        ['one_id' => 2, 'another_id' => 1],
                        ['one_id' => 2, 'another_id' => 3],
                ],
        ]);
    }
}