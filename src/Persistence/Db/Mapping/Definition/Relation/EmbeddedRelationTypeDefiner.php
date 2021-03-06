<?php declare(strict_types = 1);

namespace Dms\Core\Persistence\Db\Mapping\Definition\Relation;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Persistence\Db\Mapping\Definition\Embedded\EmbeddedCollectionDefiner;
use Dms\Core\Persistence\Db\Mapping\Definition\Embedded\EmbeddedValueObjectDefiner;
use Dms\Core\Persistence\Db\Mapping\Definition\Embedded\EnumPropertyColumnDefiner;
use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\EnumMapper;
use Dms\Core\Persistence\Db\Mapping\IEmbeddedObjectMapper;
use Dms\Core\Persistence\Db\Mapping\IObjectMapper;
use Dms\Core\Persistence\Db\Mapping\IOrm;
use Dms\Core\Persistence\Db\Mapping\NullObjectMapper;
use Dms\Core\Persistence\Db\Mapping\Relation\Embedded\EmbeddedCollectionRelation;
use Dms\Core\Persistence\Db\Mapping\Relation\Embedded\EmbeddedObjectRelation;
use Dms\Core\Persistence\Db\Schema\Column;
use Dms\Core\Persistence\Db\Schema\PrimaryKeyBuilder;
use Dms\Core\Persistence\Db\Schema\Table;
use Dms\Core\Persistence\Db\Schema\Type\Boolean;

/**
 * The embedded relation type definer class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class EmbeddedRelationTypeDefiner
{
    /**
     * @var MapperDefinition
     */
    private $definition;

    /**
     * @var IOrm
     */
    private $orm;

    /**
     * @var IAccessor
     */
    private $accessor;

    /**
     * @var callable
     */
    private $callback;

    /**
     * EmbeddedRelationTypeDefiner constructor.
     *
     * @param MapperDefinition $definition
     * @param IOrm             $orm
     * @param IAccessor        $accessor
     * @param callable         $callback
     */
    public function __construct(MapperDefinition $definition, IOrm $orm, IAccessor $accessor, callable $callback)
    {
        $this->definition = $definition;

        $this->orm      = $orm;
        $this->accessor = $accessor;
        $this->callback = $callback;
    }

    /**
     * Defines a relation mapped to an enum class.
     *
     * @see \Dms\Core\Model\Object\Enum
     *
     * @param string $class
     * @param bool   $isNullable
     *
     * @return EnumPropertyColumnDefiner
     * @throws InvalidArgumentException
     */
    public function enum(string $class, bool $isNullable = false) : EnumPropertyColumnDefiner
    {
        $callback = function (EnumMapper $enumMapper) {
            $this->definition->addColumn($enumMapper->getEnumValueColumn());

            call_user_func($this->callback, function ($idString) use ($enumMapper) {
                return new EmbeddedObjectRelation(
                    $idString,
                    $enumMapper,
                    $enumMapper->getEnumValueColumn()->getName()
                );
            });
        };

        return new EnumPropertyColumnDefiner(
            $this->definition,
            function ($columnName, array $valueMap = null) use ($callback, $class, $isNullable) {
                $callback(new EnumMapper($this->orm, $isNullable, $columnName, $class, $valueMap));
            },
            function (Column $column) use ($callback, $class, $isNullable) {
                $callback(new EnumMapper($this->orm, $isNullable, $column->getName(), $class, null, $column->getType()));
            }
        );
    }

    /**
     * Defines an embedded value object relation.
     *
     * @return EmbeddedValueObjectDefiner
     * @throws InvalidArgumentException
     */
    public function object() : EmbeddedValueObjectDefiner
    {
        return new EmbeddedValueObjectDefiner($this->orm,
            function (callable $mapperLoader, string $issetColumnName = null, bool $isUnique) {
                // Use null object mapper as parent to load the columns
                /** @var IEmbeddedObjectMapper $tempMapper */
                $tempMapper  = $mapperLoader(new NullObjectMapper());
                $columnNames = [];
                $embeddedObjectTable = $tempMapper->getDefinition()->getTable();
                
                if ($issetColumnName && !$embeddedObjectTable->hasColumn($issetColumnName)) {
                    $this->definition->addColumn(new Column($issetColumnName, new Boolean()));
                }

                foreach ($embeddedObjectTable->getColumns() as $column) {
                    $this->definition->addColumn($issetColumnName ? $column->asNullable() : $column);
                    $columnNames[] = $column->getName();
                }


                if ($isUnique) {
                    $this->definition->unique($this->definition->getTableName() . '_' . implode('_', $columnNames) . '_unique_index')
                        ->on($columnNames);
                }

                call_user_func($this->callback, function (
                    $idString,
                    Table $parentTable,
                    IObjectMapper $parentMapper
                ) use (
                    $mapperLoader,
                    $issetColumnName
                ) {
                    return new EmbeddedObjectRelation($idString, $mapperLoader($parentMapper), $issetColumnName);
                });
            });
    }

    /**
     * Defines an embedded value object collection property.
     *
     * @return EmbeddedCollectionDefiner
     * @throws InvalidArgumentException
     */
    public function collection() : EmbeddedCollectionDefiner
    {
        return new EmbeddedCollectionDefiner(
            $this->orm,
            function (callable $mapperLoader, $tableName, $primaryKeyName, $foreignKeyName) {
                call_user_func($this->callback, function (
                    $idString,
                    Table $parentTable,
                    IObjectMapper $parentMapper
                ) use (
                    $mapperLoader,
                    $tableName,
                    $primaryKeyName,
                    $foreignKeyName
                ) {
                    return new EmbeddedCollectionRelation(
                        $idString,
                        $mapperLoader($parentMapper),
                        $parentTable->getName(),
                        $this->orm->getNamespace() . $tableName,
                        $parentTable->getPrimaryKeyColumn()->withName($primaryKeyName),
                        new Column($foreignKeyName, PrimaryKeyBuilder::primaryKeyType()),
                        $parentTable->getPrimaryKeyColumn()
                    );
                });
            }
        );
    }
}