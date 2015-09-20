<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration;

use Iddigital\Cms\Core\Persistence\Db\Mapping\CustomOrm;
use Iddigital\Cms\Core\Persistence\Db\Mapping\IEntityMapper;
use Iddigital\Cms\Core\Persistence\Db\Mapping\IOrm;
use Iddigital\Cms\Core\Persistence\Db\Schema\Column;
use Iddigital\Cms\Core\Persistence\Db\Schema\Type\Enum;
use Iddigital\Cms\Core\Persistence\Db\Schema\Type\Integer;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\EmbeddedSubclassWithToManyRelation\ChildEntity;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\EmbeddedSubclassWithToManyRelation\RootEntity;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\EmbeddedSubclassWithToManyRelation\RootEntityMapper;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\EmbeddedSubclassWithToManyRelation\EntitySubclass;
use Iddigital\Cms\Core\Tests\Persistence\Db\Mock\MockDatabase;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class EmbeddedSubclassWithToManyRelationTest extends DbIntegrationTest
{
    /**
     * @inheritDoc
     */
    protected function loadOrm()
    {
        return RootEntityMapper::orm();
    }

    /**
     * @inheritDoc
     */
    protected function buildDatabase(MockDatabase $db, IOrm $orm)
    {
        parent::buildDatabase($db, $orm);

        $db->createForeignKey('children.parent_id', 'entities.id');
    }


    public function testCorrectTableLayout()
    {
        $this->assertDatabaseStructureSameAs([
                'entities' => [
                        new Column('id', Integer::normal()->autoIncrement(), true),
                        new Column('type', (new Enum(['subclass']))->nullable()),
                ],
                'children' => [
                        new Column('id', Integer::normal()->autoIncrement(), true),
                        new Column('parent_id', Integer::normal()),
                ]
        ]);
    }

    public function testPersist()
    {
        $entity = new EntitySubclass(null, [
                new ChildEntity(),
                new ChildEntity(),
                new ChildEntity(),
        ]);

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
                'entities' => [
                        ['id' => 1, 'type' => 'subclass'],
                ],
                'children' => [
                        ['id' => 1, 'parent_id' => 1],
                        ['id' => 2, 'parent_id' => 1],
                        ['id' => 3, 'parent_id' => 1],
                ],
        ]);
    }

    public function testLoad()
    {
        $this->db->setData([
                'entities' => [
                        ['id' => 1, 'type' => 'subclass'],
                ],
                'children' => [
                        ['id' => 1, 'parent_id' => 1],
                        ['id' => 2, 'parent_id' => 1],
                        ['id' => 3, 'parent_id' => 1],
                ],
        ]);

        $actual = $this->repo->get(1);

        $this->assertEquals(new EntitySubclass(1, [
                new ChildEntity(1),
                new ChildEntity(2),
                new ChildEntity(3),
        ]), $actual);
    }

    public function testRemove()
    {
        $this->db->setData([
                'entities' => [
                        ['id' => 1, 'type' => 'subclass'],
                ],
                'children' => [
                        ['id' => 1, 'parent_id' => 1],
                        ['id' => 2, 'parent_id' => 1],
                        ['id' => 3, 'parent_id' => 1],
                ],
        ]);

        $this->repo->removeById(1);

        $this->assertDatabaseDataSameAs([
                'entities' => [
                ],
                'children' => [
                ],
        ]);
    }
}