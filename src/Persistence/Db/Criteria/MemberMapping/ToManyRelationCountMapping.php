<?php

namespace Dms\Core\Persistence\Db\Criteria\MemberMapping;

use Dms\Core\Persistence\Db\Mapping\IEntityMapper;
use Dms\Core\Persistence\Db\Mapping\Relation\IRelation;
use Dms\Core\Persistence\Db\Mapping\Relation\IToManyRelation;
use Dms\Core\Persistence\Db\Query\Expression\Expr;
use Dms\Core\Persistence\Db\Query\Select;

/**
 * The to-many relation count mapping class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ToManyRelationCountMapping extends RelationMapping
{
    /**
     * @var IToManyRelation
     */
    protected $relation;

    /**
     * ToManyRelationCountMapping constructor.
     *
     * @param IEntityMapper   $rootEntityMapper
     * @param IRelation[]     $relationsToSubSelect
     * @param IToManyRelation $relation
     */
    public function __construct(IEntityMapper $rootEntityMapper, array $relationsToSubSelect, IToManyRelation $relation)
    {
        parent::__construct($rootEntityMapper, $relationsToSubSelect, $relation);
    }

    /**
     * @inheritDoc
     */
    protected function getSingleValueExpressionInSelect(Select $select, $tableAlias)
    {
        return Expr::count();
    }
}