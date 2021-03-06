<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping;

use Dms\Core\Persistence\Db\Query\Clause\Join;
use Dms\Core\Persistence\Db\Query\Expression\Expr;
use Dms\Core\Persistence\Db\Query\Select;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\ToOneThenMany\ChildEntity;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\ToOneThenMany\ParentEntityMapper;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\ToOneThenMany\SubEntity;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ToOneThanManyTest extends DbIntegrationTest
{
    /**
     * @inheritDoc
     */
    protected function loadOrm()
    {
        return ParentEntityMapper::orm();
    }

    public function testLoadCriteria()
    {
        $this->setDataInDb([
            'parents'  => [
                ['id' => 10],
                ['id' => 11],
            ],
            'subs'     => [
                ['id' => 1, 'parent_id' => 10],
                ['id' => 2, 'parent_id' => 11],
            ],
            'children' => [
                ['id' => 1, 'sub_id' => 1],
                ['id' => 2, 'sub_id' => 1],
                ['id' => 3, 'sub_id' => 1],
                ['id' => 4, 'sub_id' => 2],
                ['id' => 5, 'sub_id' => 2],
                ['id' => 6, 'sub_id' => 2],
            ],
        ]);

        $data = $this->repo->loadMatching(
            $this->repo->loadCriteria()
                ->loadAll([
                    'load(subEntityId)'                   => 'sub',
                    'load(subEntityId).loadAll(childIds)' => 'to-many',
                ])
        );

        $this->assertExecutedQueries([
            Select::from($this->getSchemaTable('parents'))
                ->join(Join::left($this->getSchemaTable('subs'), 'subs', [
                    Expr::equal(
                        Expr::tableColumn($this->getSchemaTable('parents'), 'id'),
                        Expr::tableColumn($this->getSchemaTable('subs'), 'parent_id')
                    )
                ]))
                ->addColumn('sub_id', Expr::tableColumn($this->getSchemaTable('parents'), 'id'))
                ->addColumn('to-many_id', Expr::tableColumn($this->getSchemaTable('subs'), 'id')),
            //
            Select::from($this->getSchemaTable('subs'))
                ->addColumn('id', Expr::tableColumn($this->getSchemaTable('subs'), 'id'))
                ->addColumn('parent_id', Expr::tableColumn($this->getSchemaTable('subs'), 'parent_id'))
                ->where(Expr::in(
                    Expr::tableColumn($this->getSchemaTable('subs'), 'parent_id'),
                    Expr::tuple([Expr::idParam(10), Expr::idParam(11)])
                )),
            //
            Select::from($this->getSchemaTable('children'))
                ->addColumn('id', Expr::tableColumn($this->getSchemaTable('children'), 'id'))
                ->addColumn('sub_id', Expr::tableColumn($this->getSchemaTable('children'), 'sub_id'))
                ->where(Expr::in(
                    Expr::tableColumn($this->getSchemaTable('children'), 'sub_id'),
                    Expr::tuple([Expr::idParam(1), Expr::idParam(2)])
                )),
            //
            Select::from($this->getSchemaTable('children'))
                ->where(Expr::in(
                    Expr::tableColumn($this->getSchemaTable('children'), 'sub_id'),
                    Expr::tuple([Expr::idParam(1), Expr::idParam(2)])
                ))
                ->addColumn('id', Expr::tableColumn($this->getSchemaTable('children'), 'id'))
                ->addColumn('__parent_id__', Expr::tableColumn($this->getSchemaTable('children'), 'sub_id')),
        ]);

        $this->assertEquals([
            [
                'sub'     => new SubEntity(1, [1, 2, 3]),
                'to-many' => ChildEntity::collection([
                    new ChildEntity(1),
                    new ChildEntity(2),
                    new ChildEntity(3),
                ]),
            ],
            [
                'sub'     => new SubEntity(2, [4, 5, 6]),
                'to-many' => ChildEntity::collection([
                    new ChildEntity(4),
                    new ChildEntity(5),
                    new ChildEntity(6),
                ]),
            ],
        ], $data);
    }
}