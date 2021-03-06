<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping\Relations\SelfReferencing;

use Dms\Core\Persistence\Db\Mapping\IOrm;
use Dms\Core\Persistence\Db\Query\BulkUpdate;
use Dms\Core\Persistence\Db\Query\Clause\Join;
use Dms\Core\Persistence\Db\Query\Delete;
use Dms\Core\Persistence\Db\Query\Expression\Expr;
use Dms\Core\Persistence\Db\Query\Select;
use Dms\Core\Persistence\Db\Query\Update;
use Dms\Core\Persistence\Db\Query\Upsert;
use Dms\Core\Persistence\Db\Schema\ForeignKey;
use Dms\Core\Persistence\Db\Schema\ForeignKeyMode;
use Dms\Core\Persistence\Db\Schema\PrimaryKeyBuilder;
use Dms\Core\Persistence\Db\Schema\Table;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\DbIntegrationTest;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Relations\Fixtures\SelfReferencing\ToOneRelation\RecursiveEntity;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Relations\Fixtures\SelfReferencing\ToOneRelation\RecursiveEntityMapper;
use Dms\Core\Tests\Persistence\Db\Mock\MockDatabase;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class RecursiveToOneRelationTest extends DbIntegrationTest
{
    /**
     * @var Table
     */
    protected $entities;

    /**
     * @inheritDoc
     */
    protected function loadOrm()
    {
        return RecursiveEntityMapper::orm();
    }

    /**
     * {@inheritDoc}
     */
    protected function buildDatabase(MockDatabase $db, IOrm $orm)
    {
        parent::buildDatabase($db, $orm);
        $this->entities = $db->getTable('recursive_entities')->getStructure();
    }

    public function testCreatesForeignKey()
    {
        $this->assertEquals([
            new ForeignKey(
                'fk_recursive_entities_parent_id_recursive_entities',
                ['parent_id'],
                'recursive_entities',
                ['id'],
                ForeignKeyMode::SET_NULL,
                ForeignKeyMode::CASCADE
            ),
        ], array_values($this->entities->getForeignKeys()));
    }

    protected function buildTestEntity($levels)
    {
        $main = $entity = new RecursiveEntity();

        while (--$levels) {
            $entity->parent = new RecursiveEntity();
            $entity         = $entity->parent;
        }

        return $main;
    }

    public function testCreatesForeignKeys()
    {
        $this->assertEquals(
            [
                new ForeignKey(
                    'fk_recursive_entities_parent_id_recursive_entities',
                    ['parent_id'],
                    'recursive_entities',
                    ['id'],
                    ForeignKeyMode::SET_NULL,
                    ForeignKeyMode::CASCADE
                ),
            ],
            array_values($this->entities->getForeignKeys())
        );
    }

    public function testPersistSingleLevel()
    {
        $entity = $this->buildTestEntity(1);

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => null],
            ],
        ]);

        $this->assertExecutedQueryTypes([
            'Insert entity' => Upsert::class,
        ]);
    }

    public function testPersistDeep()
    {
        $entity = $this->buildTestEntity(4);

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => null],
                ['id' => 2, 'parent_id' => 1],
                ['id' => 3, 'parent_id' => 2],
                ['id' => 4, 'parent_id' => 3],
            ],
        ]);

        // Multiple inserts necessary to get the id of the previous level
        $this->assertExecutedQueryTypes([
            'Insert level 1' => Upsert::class,
            'Insert level 2' => Upsert::class,
            'Insert level 3' => Upsert::class,
            'Insert level 4' => Upsert::class,
        ]);
    }

    public function testPersistRecursive()
    {
        $entity         = new RecursiveEntity();
        $entity->parent = $entity;

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => 1],
            ],
        ]);

        $this->assertSame(1, $entity->getId());

        $this->assertExecutedQueryTypes([
            'Insert recursive entity'    => Upsert::class,
            'Update recursive entity fk' => BulkUpdate::class,
        ]);
    }

    public function testPersistDeepExisting()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 3, 'parent_id' => null],
                ['id' => 4, 'parent_id' => 3],
            ],
        ]);

        $entity = $this->buildTestEntity(4);
        $entity->setId(4);

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 3, 'parent_id' => null],
                ['id' => 4, 'parent_id' => 3],
                ['id' => 5, 'parent_id' => 4],
                ['id' => 6, 'parent_id' => 5],
                ['id' => 7, 'parent_id' => 6],
            ],
        ]);

        // Multiple inserts necessary to get the id of the previous level
        $this->assertExecutedQueryTypes([
            'Insert level 1'              => Upsert::class,
            'Dissociate previous level 2' => Update::class,
            'Insert level 2'              => Upsert::class,
            'Insert level 3'              => Upsert::class,
            'Insert level 4'              => Upsert::class,
        ]);

        // Test removing parent association

        $entity = new RecursiveEntity();
        $entity->setId(3);

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 3, 'parent_id' => null],
                ['id' => 4, 'parent_id' => null],
                ['id' => 5, 'parent_id' => 4],
                ['id' => 6, 'parent_id' => 5],
                ['id' => 7, 'parent_id' => 6],
            ],
        ]);
    }

    public function testLoad()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => null],
                ['id' => 2, 'parent_id' => 1],
                ['id' => 3, 'parent_id' => 2],
                ['id' => 4, 'parent_id' => 3],
            ],
        ]);

        $entity = $this->buildTestEntity(4);
        $entity->setId(1);
        $entity->parent->setId(2);
        $entity->parent->parent->setId(3);
        $entity->parent->parent->parent->setId(4);
        $this->assertEquals($entity, $this->repo->get(1));

        // Multiple inserts necessary to get the id of the previous level
        $this->assertExecutedQueryTypes([
            'Select level 1'                             => Select::class,
            'Select level 2'                             => Select::class,
            'Select level 3'                             => Select::class,
            'Select level 4'                             => Select::class,
            'Try select level 5 (will be empty so null)' => Select::class,
        ]);
    }

    public function testLoadMidLevel()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => null],
                ['id' => 2, 'parent_id' => 1],
                ['id' => 3, 'parent_id' => 2],
                ['id' => 4, 'parent_id' => 3],
            ],
        ]);

        $entity = $this->buildTestEntity(2);
        $entity->setId(3);
        $entity->parent->setId(4);
        $this->assertEquals($entity, $this->repo->get(3));

        // Multiple inserts necessary to get the id of the previous level
        $this->assertExecutedQueryTypes([
            'Select level 1'                             => Select::class,
            'Select level 2'                             => Select::class,
            'Try select level 5 (will be empty so null)' => Select::class,
        ]);
    }

    public function testLoadRecursive()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => 1],
            ],
        ]);

        $entity = new RecursiveEntity();
        $entity->setId(1);
        $entity->parent = $entity;

        $this->assertEquals($entity, $this->repo->get(1));

        $this->assertExecutedQueryTypes([
            'Select recursive entity'                                         => Select::class,
            'Select parent entity (then find already loaded in identity map)' => Select::class,
        ]);
    }

    public function testLoadRecursiveDeep()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => 2],
                ['id' => 2, 'parent_id' => 1],
            ],
        ]);

        $entity = new RecursiveEntity();
        $entity->setId(1);
        $entity->parent = new RecursiveEntity();
        $entity->parent->setId(2);
        $entity->parent->parent = $entity;

        $this->assertEquals($entity, $this->repo->get(1));

        $this->assertExecutedQueryTypes([
            'Select recursive entity 1'                                       => Select::class,
            'Select recursive entity 2'                                       => Select::class,
            'Select parent entity (then find already loaded in identity map)' => Select::class,
        ]);
    }

    public function testRemove()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => null],
                ['id' => 2, 'parent_id' => 1],
                ['id' => 3, 'parent_id' => 2],
                ['id' => 4, 'parent_id' => 3],
            ],
        ]);

        $this->repo->removeById(1);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 2, 'parent_id' => null],
                ['id' => 3, 'parent_id' => 2],
                ['id' => 4, 'parent_id' => 3],
            ],
        ]);

        // Multiple inserts necessary to get the id of the previous level
        $this->assertExecutedQueryTypes([
            'Remove foreign key' => Update::class,
            'Delete level'       => Delete::class,
        ]);
    }

    public function testDeleteBulk()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => null],
                ['id' => 2, 'parent_id' => 1],
                ['id' => 3, 'parent_id' => 2],
                ['id' => 4, 'parent_id' => 3],
            ],
        ]);

        $this->repo->removeAllById([1, 3]);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 2, 'parent_id' => null],
                ['id' => 4, 'parent_id' => null],
            ],
        ]);

        // Multiple inserts necessary to get the id of the previous level
        $this->assertExecutedQueryTypes([
            'Remove foreign key' => Update::class,
            'Delete level'       => Delete::class,
        ]);
    }

    public function testRemoveRecursive()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => 1],
            ],
        ]);

        $this->repo->removeById(1);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [],
        ]);


        $this->assertExecutedQueryTypes([
            'Remove foreign key' => Update::class,
            'Delete entity'      => Delete::class,
        ]);
    }

    public function testRemoveRecursiveMultiLevel()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => 2],
                ['id' => 2, 'parent_id' => 3],
                ['id' => 3, 'parent_id' => 4],
                ['id' => 4, 'parent_id' => 1],
            ],
        ]);

        $this->repo->removeById(1);

        $this->assertDatabaseDataSameAs([
            'recursive_entities' => [
                ['id' => 2, 'parent_id' => 3],
                ['id' => 3, 'parent_id' => 4],
                ['id' => 4, 'parent_id' => null],
            ],
        ]);


        $this->assertExecutedQueryTypes([
            'Remove foreign key' => Update::class,
            'Delete entity'      => Delete::class,
        ]);
    }


    public function testLoadCriteriaWithRecursiveFlatten()
    {
        $this->setDataInDb([
            'recursive_entities' => [
                ['id' => 1, 'parent_id' => 2],
                ['id' => 2, 'parent_id' => 1],
            ],
        ]);

        $data = $this->repo->loadMatching(
            $this->repo->loadCriteria()
                ->loadAll([
                    'id',
                    'parent.parent' => 'nested_parents',
                ])
        );

        // TODO: FIX (low-pri)
        return;
        $this->assertExecutedQueries([
            Select::from($this->getSchemaTable('recursive_entities'))
                ->addRawColumn('id')
                ->addColumn('nested_parents_id', Expr::column('recursive_entities1', $this->getSchemaTable('recursive_entities')->getColumn('id')))
                ->join(Join::left($this->getSchemaTable('recursive_entities'), 'recursive_entities1', [
                    Expr::equal(
                        Expr::column('recursive_entities', $this->getSchemaTable('recursive_entities')->getColumn('id')),
                        Expr::column('recursive_entities1', $this->getSchemaTable('recursive_entities')->getColumn('parent_id'))
                    ),
                ])),
            Select::from($this->getSchemaTable('recursive_entities'))
                ->addRawColumn('id')
                ->addRawColumn('parent_id')
                ->where(Expr::in(
                    Expr::column('recursive_entities', $this->getSchemaTable('recursive_entities')->getColumn('parent_id')),
                    Expr::tupleParams( $this->getSchemaTable('recursive_entities')->getColumn('parent_id')->getType(), [2, 1])
                )),
            Select::from($this->getSchemaTable('recursive_entities'))
                ->addRawColumn('id')
                ->addRawColumn('parent_id')
                ->where(Expr::in(
                    Expr::column('recursive_entities', $this->getSchemaTable('recursive_entities')->getColumn('parent_id')),
                    Expr::tupleParams( $this->getSchemaTable('recursive_entities')->getColumn('parent_id')->getType(), [1, 2])
                )),
        ]);

        $this->assertEquals(
            [
                ['id' => 1, 'nested_parents' => $this->repo->get(2)],
                ['id' => 2, 'nested_parents' => $this->repo->get(1)],
            ],
            $data
        );
    }
}