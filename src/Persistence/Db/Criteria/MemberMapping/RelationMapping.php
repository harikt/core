<?php declare(strict_types = 1);

namespace Dms\Core\Persistence\Db\Criteria\MemberMapping;

use Dms\Core\Persistence\Db\Mapping\IEntityMapper;
use Dms\Core\Persistence\Db\Mapping\Relation\IRelation;
use Dms\Core\Persistence\Db\Mapping\Relation\IToManyRelation;
use Dms\Core\Persistence\Db\Mapping\Relation\IToOneRelation;

/**
 * The relation mapping base class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
abstract class RelationMapping extends MemberMapping
{
    /**
     * @var IRelation
     */
    protected $relation;

    /**
     * RelationMapping constructor.
     *
     * @param IEntityMapper $rootEntityMapper
     * @param IRelation[]   $relationsToSubSelect
     * @param IRelation     $relation
     */
    public function __construct(IEntityMapper $rootEntityMapper, array $relationsToSubSelect, IRelation $relation)
    {
        parent::__construct($rootEntityMapper, array_merge($relationsToSubSelect, [$relation]));
        $this->relation = $relation;
    }

    /**
     * @return IToOneRelation|IToManyRelation
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @return IRelation
     */
    public function getFirstRelation() : \Dms\Core\Persistence\Db\Mapping\Relation\IRelation
    {
        return reset($this->relationsToSubSelect) ?: $this->relation;
    }

    /**
     * @return string
     */
    protected function getRelatedObjectType() : string
    {
        return $this->relation->getMapper()->getObjectType();
    }
}