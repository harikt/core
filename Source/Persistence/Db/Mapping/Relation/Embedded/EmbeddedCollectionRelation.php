<?php

namespace Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\Embedded;

use Iddigital\Cms\Core\Exception\NotImplementedException;
use Iddigital\Cms\Core\Exception\TypeMismatchException;
use Iddigital\Cms\Core\Model\ITypedCollection;
use Iddigital\Cms\Core\Model\ValueObjectCollection;
use Iddigital\Cms\Core\Persistence\Db\LoadingContext;
use Iddigital\Cms\Core\Persistence\Db\Mapping\IEmbeddedObjectMapper;
use Iddigital\Cms\Core\Persistence\Db\Mapping\ParentChildrenMap;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\IToManyRelation;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\Mode\IdentifyingRelationMode;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\Reference\IToManyRelationReference;
use Iddigital\Cms\Core\Persistence\Db\PersistenceContext;
use Iddigital\Cms\Core\Persistence\Db\Query\Delete;
use Iddigital\Cms\Core\Persistence\Db\Query\Expression\Expr;
use Iddigital\Cms\Core\Persistence\Db\Query\Select;
use Iddigital\Cms\Core\Persistence\Db\Row;
use Iddigital\Cms\Core\Persistence\Db\Schema\Column;
use Iddigital\Cms\Core\Persistence\Db\Schema\ForeignKey;
use Iddigital\Cms\Core\Persistence\Db\Schema\ForeignKeyMode;
use Iddigital\Cms\Core\Persistence\Db\Schema\Table;

/**
 * The embedded collection relation class.
 *
 * This will map the children objects to a separate table that does
 * not contain a primary key but is related by a foreign key to the parent.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class EmbeddedCollectionRelation extends EmbeddedRelation implements IToManyRelation
{
    /**
     * @var IdentifyingRelationMode
     */
    private $mode;

    /**
     * @var string
     */
    private $parentTableName;

    /**
     * @var Column
     */
    private $parentPrimaryKey;

    /**
     * @var Column
     */
    private $primaryKey;

    /**
     * @var Column
     */
    private $foreignKeyToParent;

    /**
     * @var Table
     */
    private $childrenTable;

    /**
     * @param IEmbeddedObjectMapper $mapper
     * @param string                $tableName
     * @param string                $parentTableName
     * @param Column                $childPrimaryKey
     * @param Column                $foreignKeyToParentColumn
     * @param Column                $parentPrimaryKey
     */
    public function __construct(
            IEmbeddedObjectMapper $mapper,
            $parentTableName,
            $tableName,
            Column $childPrimaryKey,
            Column $foreignKeyToParentColumn,
            Column $parentPrimaryKey
    ) {
        $this->parentTableName    = $parentTableName;
        $this->parentPrimaryKey   = $parentPrimaryKey;
        $this->primaryKey         = $childPrimaryKey;
        $this->foreignKeyToParent = $foreignKeyToParentColumn;
        $this->childrenTable      = $this->buildChildrenTable($mapper, $tableName);
        $mapper                   = $mapper->asSeparateTable($this->childrenTable);

        parent::__construct($mapper, self::DEPENDENT_CHILDREN, [$this->childrenTable], [$parentPrimaryKey->getName()]);

        // Embedded values objects are always identifying
        // They must have be associated with a parent object
        $this->mode = new IdentifyingRelationMode();
    }

    /**
     * @inheritDoc
     */
    public function withReference(IToManyRelationReference $reference)
    {
        // TODO: determine if necessary
        throw NotImplementedException::method(__METHOD__);
    }

    private function buildChildrenTable(IEmbeddedObjectMapper $mapper, $tableName)
    {
        $table = $mapper->getDefinition()->getTable();

        return $table
                ->withName($tableName)
                ->withColumns(array_merge(
                        [$this->primaryKey, $this->foreignKeyToParent],
                        $table->getColumns()
                ))
                ->withForeignKeys(array_merge(
                        [
                                ForeignKey::createWithNamingConvention(
                                        $tableName,
                                        [$this->foreignKeyToParent->getName()],
                                        $this->parentTableName,
                                        [$this->parentPrimaryKey->getName()],
                                        ForeignKeyMode::CASCADE,
                                        ForeignKeyMode::CASCADE
                                )
                        ],
                        $table->getForeignKeys()
                ));
    }

    /**
     * @param array $children
     *
     * @return ITypedCollection
     */
    public function buildCollection(array $children)
    {
        return new ValueObjectCollection($this->mapper->getObjectType(), $children);
    }

    /**
     * @param PersistenceContext $context
     * @param Delete             $parentDelete
     *
     * @return void
     */
    public function delete(PersistenceContext $context, Delete $parentDelete)
    {
        $this->mode->removeRelationsQuery(
                $context,
                $this->mapper,
                $parentDelete,
                $this->childrenTable,
                $this->foreignKeyToParent,
                $parentDelete->getTable()->getPrimaryKeyColumn()
        );
    }

    /**
     * @param PersistenceContext $context
     * @param ParentChildrenMap  $map
     *
     * @return void
     * @throws TypeMismatchException
     */
    public function persist(PersistenceContext $context, ParentChildrenMap $map)
    {
        if ($map->hasAnyParentsWithPrimaryKeys()) {
            $this->mode->syncInvalidatedRelationsQuery(
                    $context,
                    $this->childrenTable,
                    $this->foreignKeyToParent,
                    $this->getInvalidatedRelationExpr($map)
            );
        }

        $this->insertRelated($context, $map);
    }


    /**
     * @param PersistenceContext $context
     * @param ParentChildrenMap  $map
     *
     * @return void
     */
    protected function insertRelated(PersistenceContext $context, ParentChildrenMap $map)
    {
        $primaryKey = $map->getPrimaryKeyColumn();
        /** @var Row[] $parents */
        $parents = [];
        /** @var object[] $children */
        $children          = [];
        $childRows         = [];
        $childKeyParentMap = [];

        foreach ($map->getItems() as $key => $item) {
            $parents[$key] = $item->getParent();

            foreach ($item->getChildren() as $childKey => $child) {
                $uniqueKey                     = $key . '|' . $childKey;
                $children[$uniqueKey]          = $child;
                $childKeyParentMap[$uniqueKey] = $key;
                $childRows[$uniqueKey]         = new Row($this->childrenTable);
            }
        }

        $this->mapper->persistAllToRows($context, $children, $childRows);
        $rowGroups = [];

        foreach ($childKeyParentMap as $rowKey => $parentKey) {
            $rowGroups[$parentKey][] = $childRows[$rowKey];
        }

        foreach ($map->getItems() as $key => $item) {
            $parentRow = $item->getParent();
            $childRows = isset($rowGroups[$key]) ? $rowGroups[$key] : [];

            if ($parentRow->hasColumn($primaryKey)) {
                $this->setForeignKey($childRows, $this->foreignKeyToParent->getName(), $parentRow->getColumn($primaryKey));
            } else {
                $parentRow->onInsertPrimaryKey(function ($id) use ($childRows) {
                    $this->setForeignKey($childRows, $this->foreignKeyToParent->getName(), $id);
                });
            }
        }
    }

    protected function getInvalidatedRelationExpr(ParentChildrenMap $map)
    {
        // foreign_key_to_parent IN (<parent keys>)
        $parentIds = [];

        foreach ($map->getAllParentPrimaryKeys() as $key) {
            $parentIds[] = Expr::idParam($key);
        }

        return $parentIds
                ? Expr::in(
                        Expr::tableColumn($this->childrenTable, $this->foreignKeyToParent->getName()),
                        Expr::tuple($parentIds)
                )
                : Expr::false();
    }

    /**
     * @param LoadingContext    $context
     * @param ParentChildrenMap $map
     *
     * @return mixed
     */
    public function load(LoadingContext $context, ParentChildrenMap $map)
    {
        $primaryKey = $map->getPrimaryKeyColumn();
        $parentIds  = [];
        foreach ($map->getAllParentPrimaryKeys() as $id) {
            $parentIds[] = Expr::idParam($id);
        }

        $foreignKeyName = $this->foreignKeyToParent->getName();
        $select         = Select::from($this->childrenTable);
        $this->mapper->getMapping()->addLoadToSelect($select);
        $select->addRawColumn($foreignKeyName);
        $select->where(Expr::in(
                Expr::column(
                        $this->childrenTable->getName(),
                        $this->foreignKeyToParent
                ),
                Expr::tuple($parentIds)
        ));

        $indexedGroups = [];

        $rows = $context->query($select)->getRows();

        foreach ($rows as $row) {
            $indexedGroups[$row->getColumn($foreignKeyName)][] = $row;
        }

        $flattenedResults = [];

        foreach ($indexedGroups as $parentKey => $group) {
            foreach ($group as $key => $row) {
                $flattenedResults[$parentKey . '|' . $key] = $row;
            }
        }

        $allChildren = $this->mapper->loadAll($context, $flattenedResults);

        foreach ($map->getItems() as $item) {
            $parentKey = $item->getParent()->getColumn($primaryKey);
            $children  = [];

            if (isset($indexedGroups[$parentKey])) {
                foreach ($indexedGroups[$parentKey] as $key => $row) {
                    $children[] = $allChildren[$parentKey . '|' . $key];
                }
            }

            $item->setChildren($children);
        }
    }
}