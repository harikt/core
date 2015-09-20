<?php

namespace Iddigital\Cms\Core\Persistence\Db\Mapping\Definition\Relation;

use Iddigital\Cms\Core\Persistence\Db\Mapping\IEntityMapper;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\Mode\IdentifyingRelationMode;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\Mode\NonIdentifyingRelationMode;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\Reference\ToOneRelationIdentityReference;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\Reference\ToOneRelationObjectReference;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Relation\ToOneRelation;
use Iddigital\Cms\Core\Persistence\Db\Schema\ForeignKey;
use Iddigital\Cms\Core\Persistence\Db\Schema\ForeignKeyMode;
use Iddigital\Cms\Core\Persistence\Db\Schema\Table;

/**
 * The to-one relation definer class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ToOneRelationDefiner extends RelationTypeDefinerBase
{
    /**
     * @var bool
     */
    private $identifying = false;

    /**
     * @var string|null
     */
    private $bidirectionalRelationProperty;

    /**
     * Defines the relationship as an identifying relationship.
     *
     * This means that when related entities are removed from
     * the relationship, they are globally deleted.
     *
     * @return static
     */
    public function identifying()
    {
        $this->identifying = true;

        return $this;
    }

    /**
     * Defines the bidirectional relation property that is defined
     * on the related entity.
     *
     * The bidirectional relation for a one-to-one relation should
     * be one-to-one.
     *
     * @param string $propertyOnRelatedEntity
     *
     * @return static
     */
    public function withBidirectionalRelation($propertyOnRelatedEntity)
    {
        $this->bidirectionalRelationProperty = $propertyOnRelatedEntity;

        return $this;
    }

    /**
     * Sets the column name of the foreign key on the
     * related table.
     *
     * @param string $columnName
     *
     * @return void
     */
    public function withParentIdAs($columnName)
    {
        call_user_func($this->callback, function (Table $parentTable) use ($columnName) {
            /** @var IEntityMapper $mapper */
            $mapper = call_user_func($this->mapperLoader);

            $mapper->addForeignKey(ForeignKey::createWithNamingConvention(
                    $mapper->getPrimaryTableName(),
                    [$columnName],
                    $parentTable->getName(),
                    [$parentTable->getPrimaryKeyColumnName()],
                    ForeignKeyMode::CASCADE,
                    $this->identifying
                            ? ForeignKeyMode::CASCADE
                            : ForeignKeyMode::SET_NULL
            ));

            return new ToOneRelation(
                    $this->loadIds
                            ? new ToOneRelationIdentityReference($mapper)
                            : new ToOneRelationObjectReference($mapper, $this->bidirectionalRelationProperty),
                    $columnName,
                    $this->identifying
                            ? new IdentifyingRelationMode()
                            : new NonIdentifyingRelationMode()
            );
        });
    }
}