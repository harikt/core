<?php

namespace Dms\Core\Persistence\Db\Mapping\Relation;

use Dms\Core\Persistence\Db\LoadingContext;
use Dms\Core\Persistence\Db\Mapping\ParentChildMap;
use Dms\Core\Persistence\Db\Mapping\ParentMapBase;
use Dms\Core\Persistence\Db\Mapping\Relation\Reference\IToOneRelationReference;
use Dms\Core\Persistence\Db\PersistenceContext;
use Dms\Core\Persistence\Db\Query\Clause\Join;
use Dms\Core\Persistence\Db\Query\Delete;
use Dms\Core\Persistence\Db\Query\Expression\Expr;
use Dms\Core\Persistence\Db\Query\Select;
use Dms\Core\Persistence\Db\Row;
use Dms\Core\Persistence\Db\Schema\Column;
use Dms\Core\Persistence\Db\Schema\Table;

/**
 * The many to one relation base class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ManyToOneRelation extends ToOneRelationBase
{
    /**
     * @var Table
     */
    private $parentTable;

    /**
     * @var string
     */
    protected $foreignKeyToRelated;

    /**
     * @var Column
     */
    protected $foreignKeyColumn;

    /**
     * @param string                  $idString
     * @param IToOneRelationReference $reference
     * @param Table                   $parentTable
     * @param string                  $foreignKeyToRelated
     *
     * @throws InvalidRelationException
     */
    public function __construct($idString, IToOneRelationReference $reference, Table $parentTable, $foreignKeyToRelated)
    {
        parent::__construct($idString, $reference, null, self::DEPENDENT_PARENTS, [], [$foreignKeyToRelated]);
        $this->parentTable         = $parentTable;
        $this->foreignKeyToRelated = $foreignKeyToRelated;
        $this->foreignKeyColumn    = $parentTable->findColumn($foreignKeyToRelated);

        if (!$this->foreignKeyColumn) {
            throw InvalidRelationException::format(
                    'Invalid related foreign key column %s does not exist on parent table %s',
                    $foreignKeyToRelated, $parentTable->getName()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function withReference(IToOneRelationReference $reference)
    {
        return new self($this->idString, $reference, $this->parentTable, $this->foreignKeyToRelated);
    }

    public function persist(PersistenceContext $context, ParentChildMap $map)
    {
        $this->insertRelated($context, $map);
    }

    protected function deleteByParentQuery(PersistenceContext $context, Delete $parentDelete)
    {
        // Many-to-one relation does not delete related entities
        // as they are shared between many parents
    }

    /**
     * @param PersistenceContext $context
     * @param ParentChildMap     $map
     *
     * @return void
     */
    protected function insertRelated(PersistenceContext $context, ParentChildMap $map)
    {
        /** @var Row[] $parents */
        $parents = [];
        /** @var array $children */
        $children = [];

        foreach ($map->getItems() as $key => $item) {
            $parents[$key]  = $item->getParent();
            $children[$key] = $item->getChild();
        }

        $primaryKey = $this->mapper->getPrimaryTable()->getPrimaryKeyColumnName();
        $rows       = $this->reference->syncRelated($context, [], $children);

        foreach ($rows as $key => $row) {
            $parent = $parents[$key];

            if ($row->hasColumn($primaryKey)) {
                $this->setForeignKey([$parent], $this->foreignKeyToRelated, $row->getColumn($primaryKey));
            } else {
                $row->onInsertPrimaryKey(function ($id) use ($parent) {
                    $this->setForeignKey([$parent], $this->foreignKeyToRelated, $id);
                });
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getRelationSelectFromParentRows(ParentMapBase $map, &$parentIdColumnName = null)
    {
        $relatedPrimaryKey     = $this->mapper->getPrimaryTable()->getPrimaryKeyColumn();
        $relatedPrimaryKeyName = $relatedPrimaryKey->getName();
        $parentIds             = [];

        foreach ($map->getAllParents() as $parent) {
            $parentIds[] = Expr::idParam($parent->getColumn($this->foreignKeyToRelated));
        }

        $select = $this->select();
        $select->addRawColumn($relatedPrimaryKeyName);
        $select->where(Expr::in($this->column($relatedPrimaryKey), Expr::tuple($parentIds)));

        $parentIdColumnName = $relatedPrimaryKeyName;

        return $select;
    }

    /**
     * @inheritDoc
     */
    public function loadFromSelect(LoadingContext $context, ParentChildMap $map, Select $select, $relatedTableAlias, $parentIdColumnName)
    {
        $this->reference->addLoadToSelect($select, $relatedTableAlias);

        $indexedResults = [];

        $rows = $context->query($select)->getRows();
        foreach ($rows as $row) {
            $indexedResults[$row->getColumn($parentIdColumnName)] = $row;
        }

        $values = $this->reference->loadValues($context, $indexedResults);

        foreach ($map->getItems() as $item) {
            $parentKey = $item->getParent()->getColumn($this->foreignKeyToRelated);
            $item->setChild(isset($values[$parentKey]) ? $values[$parentKey] : null);
        }
    }


    /**
     * @inheritDoc
     */
    public function joinSelectToRelatedTable($parentTableAlias, $joinType, Select $select)
    {
        $relatedTableAlias = $select->generateUniqueAliasFor($this->relatedTable->getName());

        $select->join(new Join($joinType, $this->relatedTable, $relatedTableAlias, [
                $this->getRelationJoinCondition($parentTableAlias, $relatedTableAlias)
        ]));

        return $relatedTableAlias;
    }

    /**
     * @inheritDoc
     */
    public function getRelationJoinCondition($parentTableAlias, $relatedTableAlias)
    {
        return Expr::equal(
                Expr::column($parentTableAlias, $this->foreignKeyColumn),
                Expr::column($relatedTableAlias, $this->relatedPrimaryKey)
        );
    }
}