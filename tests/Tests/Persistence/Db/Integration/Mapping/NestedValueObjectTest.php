<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping;

use Dms\Core\Persistence\Db\Mapping\CustomOrm;
use Dms\Core\Persistence\Db\Query\Select;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\NestedValueObject\LevelOne;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\NestedValueObject\LevelThree;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\NestedValueObject\LevelTwo;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\NestedValueObject\ParentEntity;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\NestedValueObject\ParentEntityMapper;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class NestedValueObjectTest extends DbIntegrationTest
{
    /**
     * @inheritDoc
     */
    protected function loadOrm()
    {
        return CustomOrm::from([ParentEntity::class => ParentEntityMapper::class]);
    }

    protected function getTestEntity()
    {
        $entity      = new ParentEntity();
        $entity->one = new LevelOne(new LevelTwo(new LevelThree('foobar')));

        return $entity;
    }

    public function testPersist()
    {
        $entity = $this->getTestEntity();

        $this->repo->save($entity);

        $this->assertTrue($entity->hasId());
        $this->assertSame(1, $entity->getId());

        $this->assertDatabaseDataSameAs([
                'parents' => [
                        ['id' => 1, 'one_two_three_value' => 'foobar']
                ]
        ]);
    }

    public function testLoad()
    {
        $entity = $this->getTestEntity();

        $this->repo->save($entity);

        $this->assertEquals($entity, $this->repo->get(1));
        $this->assertSame('foobar', $this->repo->get(1)->one->two->three->val);
    }

    public function testLoadPartial()
    {
        $this->setDataInDb([
                'parents' => [
                        ['id' => 1, 'one_two_three_value' => 'foobar']
                ]
        ]);

        $this->assertEquals(
                [
                        [
                                'one'               => new LevelOne(new LevelTwo(new LevelThree('foobar'))),
                                'one.two'           => new LevelTwo(new LevelThree('foobar')),
                                'one.two.three'     => new LevelThree('foobar'),
                                'one.two.three.val' => 'foobar',
                        ]
                ],
                $this->repo->loadMatching(
                        $this->repo->loadCriteria()
                                ->loadAll(['one', 'one.two', 'one.two.three', 'one.two.three.val'])
                )
        );

        $this->assertExecutedQueryTypes([
                'Select parent with nested value objects' => Select::class,
        ]);
    }
}