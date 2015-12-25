<?php

namespace Dms\Core\Persistence\Db\Mapping\Definition;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Model\Object\FinalizedClassDefinition;
use Dms\Core\Persistence\Db\Mapping\Definition\Relation\Accessor\PropertyAccessor;
use Dms\Core\Persistence\Db\Mapping\Definition\Relation\RelationMapping;
use Dms\Core\Persistence\Db\Mapping\Definition\Relation\ToManyRelationMapping;
use Dms\Core\Persistence\Db\Mapping\Definition\Relation\ToOneRelationMapping;
use Dms\Core\Persistence\Db\Mapping\Hierarchy\IObjectMapping;
use Dms\Core\Persistence\Db\Mapping\Hook\IPersistHook;
use Dms\Core\Persistence\Db\Mapping\IEmbeddedObjectMapper;
use Dms\Core\Persistence\Db\Mapping\IObjectMapper;
use Dms\Core\Persistence\Db\Mapping\IOrm;
use Dms\Core\Persistence\Db\Mapping\Locking\IOptimisticLockingStrategy;
use Dms\Core\Persistence\Db\Mapping\NullObjectMapper;
use Dms\Core\Persistence\Db\Mapping\Relation\IRelation;
use Dms\Core\Persistence\Db\Schema\ForeignKey;
use Dms\Core\Persistence\Db\Schema\Table;
use Dms\Core\Util\Debug;

/**
 * The finalized mapper definition class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class FinalizedMapperDefinition extends MapperDefinitionBase
{
    /**
     * @var bool
     */
    private $hasInitializedRelations = false;

    /**
     * @var IOrm
     */
    private $orm;

    /**
     * @var Table
     */
    private $table;

    /**
     * @var Table
     */
    private $entityTable;

    /**
     * @var IObjectMapping[]
     */
    private $subClassMappings = [];

    /**
     * @var array
     */
    private $propertyColumnNameMap;

    /**
     * @var callable[]
     */
    protected $columnSetterMap = [];

    /**
     * @var callable[]
     */
    protected $columnGetterMap = [];

    /**
     * @var string[]
     */
    protected $methodColumnNameMap = [];

    /**
     * @var ToOneRelationMapping[]
     */
    protected $toOneRelations = [];

    /**
     * @var ToManyRelationMapping[]
     */
    protected $toManyRelations = [];

    /**
     * @var array|\callable[]
     */
    private $phpToDbPropertyConverterMap;

    /**
     * @var array|\callable[]
     */
    private $dbToPhpPropertyConverterMap;

    /**
     * @var IOptimisticLockingStrategy[]
     */
    private $lockingStrategies = [];

    /**
     * @var IPersistHook[]
     */
    private $persistHooks = [];

    /**
     * @var callable
     */
    private $relationMappingsFactory;

    /**
     * @var callable
     */
    private $foreignKeysFactory;

    /**
     * FinalizedMapperDefinition constructor.
     *
     * @param IOrm                         $orm
     * @param FinalizedClassDefinition     $class
     * @param Table                        $table
     * @param string[]                     $propertyColumnNameMap
     * @param callable[]                   $columnGetterMap
     * @param callable[]                   $columnSetterMap
     * @param callable[]                   $phpToDbPropertyConverterMap
     * @param callable[]                   $dbToPhpPropertyConverterMap
     * @param string[]                     $methodColumnNameMap
     * @param IOptimisticLockingStrategy[] $lockingStrategies
     * @param IPersistHook[]               $persistHooks
     * @param IObjectMapping[]             $subClassMappings
     * @param callable                     $relationMappingsFactory
     * @param callable                     $foreignKeysFactory
     * @param Table|null                   $entityTable
     */
    public function __construct(
            IOrm $orm,
            FinalizedClassDefinition $class,
            Table $table,
            array $propertyColumnNameMap,
            array $columnGetterMap,
            array $columnSetterMap,
            array $phpToDbPropertyConverterMap,
            array $dbToPhpPropertyConverterMap,
            array $methodColumnNameMap,
            array $lockingStrategies,
            array $persistHooks,
            array $subClassMappings,
            callable $relationMappingsFactory,
            callable $foreignKeysFactory,
            Table $entityTable = null
    ) {
        $this->orm                         = $orm;
        $this->class                       = $class;
        $this->table                       = $table;
        $this->propertyColumnNameMap       = $propertyColumnNameMap;
        $this->columnGetterMap             = $columnGetterMap;
        $this->columnSetterMap             = $columnSetterMap;
        $this->phpToDbPropertyConverterMap = $phpToDbPropertyConverterMap;
        $this->dbToPhpPropertyConverterMap = $dbToPhpPropertyConverterMap;
        $this->methodColumnNameMap         = $methodColumnNameMap;
        $this->lockingStrategies           = $lockingStrategies;

        foreach ($persistHooks as $persistHook) {
            $this->persistHooks[$persistHook->getIdString()] = $persistHook;
        }

        foreach ($subClassMappings as $mapping) {
            $this->subClassMappings[$mapping->getObjectType()] = $mapping;
        }

        $this->relationMappingsFactory = $relationMappingsFactory;
        $this->foreignKeysFactory      = $foreignKeysFactory;
        $this->entityTable             = $entityTable ?: $table;
    }

    /**
     * @param IObjectMapper $mapper
     *
     * @throws InvalidArgumentException
     */
    public function initializeRelations(IObjectMapper $mapper)
    {
        if ($this->hasInitializedRelations) {
            return;
        }

        if ($mapper instanceof IEmbeddedObjectMapper && $mapper->getRootEntityMapper()) {
            $this->entityTable = $mapper->getRootEntityMapper()->getPrimaryTable();
        } else {
            $this->entityTable = $this->table;
        }

        $relationMappings = call_user_func($this->relationMappingsFactory, $this->entityTable, $mapper);

        foreach ($relationMappings as $relationMapping) {
            if ($relationMapping instanceof ToOneRelationMapping) {
                $this->toOneRelations[] = $relationMapping;
            } elseif ($relationMapping instanceof ToManyRelationMapping) {
                $this->toManyRelations[] = $relationMapping;
            } else {
                throw InvalidArgumentException::format('Unknown relation mapping type: %s', Debug::getType($relationMapping));
            }
        }

        $this->table = $this->table->withForeignKeys(array_merge(
                $this->table->getForeignKeys(),
                call_user_func($this->foreignKeysFactory, $this->table)
        ));

        foreach ($this->subClassMappings as $mapping) {
            $mapping->initializeRelations($mapper);
        }

        $this->hasInitializedRelations = true;
    }

    /**
     * @param ForeignKey $foreignKey
     *
     * @return void
     */
    public function addForeignKey(ForeignKey $foreignKey)
    {
        $this->table = $this->table->withForeignKeys(
                array_merge($this->table->getForeignKeys(), [$foreignKey])
        );
    }

    /**
     * @param IPersistHook $persistHook
     *
     * @return void
     */
    public function addPersistHook(IPersistHook $persistHook)
    {
        $this->persistHooks[$persistHook->getIdString()] = $persistHook;
    }

    /**
     * @return IOrm
     */
    public function getOrm()
    {
        return $this->orm;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->class->getClassName();
    }

    /**
     * Returns the equivalent definitions with the columns
     * prefixed by the supplied string.
     *
     * @param string $prefix
     *
     * @return FinalizedMapperDefinition
     */
    public function withColumnsPrefixedBy($prefix)
    {
        if ($prefix === '') {
            return $this;
        }

        $table       = $this->table->withPrefix($prefix);
        $entityTable = $this->entityTable->withPrefix($prefix);

        $propertyColumnNameMap = [];
        foreach ($this->propertyColumnNameMap as $property => $column) {
            $propertyColumnNameMap[$property] = $prefix . $column;
        }

        $columnGetterMap = [];
        foreach ($this->columnGetterMap as $column => $getter) {
            $columnGetterMap[$prefix . $column] = $getter;
        }

        $columnSetterMap = [];
        foreach ($this->columnSetterMap as $column => $setter) {
            $columnSetterMap[$prefix . $column] = $setter;
        }

        $methodColumnNameMap = [];
        foreach ($this->methodColumnNameMap as $property => $column) {
            $methodColumnNameMap[$property] = $prefix . $column;
        }

        $lockingStrategies = [];
        foreach ($this->lockingStrategies as $key => $lockingStrategy) {
            $lockingStrategies[$key] = $lockingStrategy->withColumnNamesPrefixedBy($prefix);
        }

        $persistHooks = [];
        foreach ($this->persistHooks as $key => $persistHook) {
            $persistHooks[$key] = $persistHook->withColumnNamesPrefixedBy($prefix);
        }

        if ($this->hasInitializedRelations) {
            $relationMappingsFactory = function () {
                return $this->getRelationMappings();
            };

            $foreignKeyFactory = function () {
                return [];
            };
        } else {
            $relationMappingsFactory = $this->relationMappingsFactory;
            $foreignKeyFactory       = $this->foreignKeysFactory;
        }

        $relationMappingsFactory = function (Table $parentTable, IObjectMapper $parentMapper) use ($relationMappingsFactory, $prefix) {
            $mappings = [];

            /** @var RelationMapping $mapping */
            foreach ($relationMappingsFactory($parentTable, $parentMapper) as $mapping) {
                $mappings[] = $mapping->withEmbeddedColumnsPrefixedBy($prefix);
            }

            return $mappings;
        };

        $foreignKeyFactory = function (Table $parentTable) use ($foreignKeyFactory, $prefix) {
            $foreignKeys = [];
            /** @var ForeignKey $foreignKey */
            foreach ($foreignKeyFactory($parentTable) as $foreignKey) {
                $foreignKeys[] = $foreignKey->withPrefix($prefix);
            }

            return $foreignKeys;
        };

        $subClassMappings = [];
        foreach ($this->subClassMappings as $mapping) {
            $subClassMappings[] = $mapping->withEmbeddedColumnsPrefixedBy($prefix);
        }

        $self = new self(
                $this->orm,
                $this->class,
                $table,
                $propertyColumnNameMap,
                $columnGetterMap,
                $columnSetterMap,
                $this->phpToDbPropertyConverterMap,
                $this->dbToPhpPropertyConverterMap,
                $methodColumnNameMap,
                $lockingStrategies,
                $persistHooks,
                $subClassMappings,
                $relationMappingsFactory,
                $foreignKeyFactory,
                $entityTable
        );

        if ($this->hasInitializedRelations) {
            $self->initializeRelations(new NullObjectMapper());
        }

        return $self;
    }

    /**
     * @return FinalizedClassDefinition
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Gets the table structure for the entity that contains this definition.
     *
     * @return Table
     */
    public function getEntityTable()
    {
        return $this->entityTable;
    }

    /**
     * @return string[]
     */
    public function getPropertyColumnMap()
    {
        return $this->propertyColumnNameMap;
    }

    /**
     * @return callable[]
     */
    public function getPhpToDbPropertyConverterMap()
    {
        return $this->phpToDbPropertyConverterMap;
    }

    /**
     * @return callable[]
     */
    public function getDbToPhpPropertyConverterMap()
    {
        return $this->dbToPhpPropertyConverterMap;
    }

    /**
     * @return callable[]
     */
    public function getColumnGetterMap()
    {
        return $this->columnGetterMap;
    }

    /**
     * @return callable[]
     */
    public function getColumnSetterMap()
    {
        return $this->columnSetterMap;
    }

    /**
     * @return string[]
     */
    public function getMethodColumnMap()
    {
        return $this->methodColumnNameMap;
    }

    /**
     * Gets the relations mapped to properties.
     *
     * @return IRelation[]
     */
    public function getPropertyRelationMap()
    {
        $relations = [];

        foreach ($this->getRelationMappings() as $mapping) {
            $accessor = $mapping->getAccessor();

            if ($accessor instanceof PropertyAccessor) {
                $relations[$accessor->getPropertyName()] = $mapping->getRelation();
            }
        }

        return $relations;
    }

    /**
     * @return RelationMapping[]
     */
    public function getRelationMappings()
    {
        return array_merge($this->toOneRelations, $this->toManyRelations);
    }

    /**
     * @return ToOneRelationMapping[]
     */
    public function getToOneRelationMappings()
    {
        return $this->toOneRelations;
    }

    /**
     * @return ToManyRelationMapping[]
     */
    public function getToManyRelationMappings()
    {
        return $this->toManyRelations;
    }

    /**
     * @return IRelation[]
     */
    public function getRelations()
    {
        $relations = [];

        foreach ($this->getRelationMappings() as $mapping) {
            $relations[] = $mapping->getRelation();
        }

        return $relations;
    }

    /**
     * @param string $property
     *
     * @return IRelation|null
     */
    public function getRelationMappedToProperty($property)
    {
        InvalidArgumentException::verify(is_string($property), 'property must be a string');
        $relations = $this->getRelationMappings();

        foreach ($relations as $mapping) {
            $accessor = $mapping->getAccessor();

            if ($accessor instanceof PropertyAccessor) {
                if ($accessor->getPropertyName() === $property) {
                    
                    return $mapping->getRelation();
                }
            }
        }

        return null;
    }

    /**
     * @param string $dependencyMode
     *
     * @return RelationMapping[]
     */
    public function getRelationMappingsWith($dependencyMode)
    {
        $mappings = [];

        foreach ($this->getRelationMappings() as $mapping) {
            if ($mapping->getRelation()->getDependencyMode() === $dependencyMode) {
                $mappings[] = $mapping;
            }
        }

        return $mappings;
    }

    /**
     * @return IOptimisticLockingStrategy[]
     */
    public function getLockingStrategies()
    {
        return $this->lockingStrategies;
    }

    /**
     * @return IPersistHook[]
     */
    public function getPersistHooks()
    {
        return $this->persistHooks;
    }

    /**
     * @param string $idString
     *
     * @return IPersistHook|null
     */
    public function getPersistHook($idString)
    {
        return isset($this->persistHooks[$idString]) ? $this->persistHooks[$idString] : null;
    }

    /**
     * @return IObjectMapping[]
     */
    public function getSubClassMappings()
    {
        return $this->subClassMappings;
    }

    /**
     * @param string $dependencyMode
     *
     * @return IObjectMapping[]
     */
    public function getSubClassMappingsWith($dependencyMode)
    {
        $mappings = [];

        foreach ($this->getSubClassMappings() as $classType => $mapping) {
            if ($mapping->getDependencyMode() === $dependencyMode) {
                $mappings[$classType] = $mapping;
            }
        }

        return $mappings;
    }

    /**
     * @return bool
     */
    public function isForAbstractClass()
    {
        return $this->class->isAbstract();
    }

    /**
     * @param Table $table
     *
     * @return FinalizedMapperDefinition
     */
    public function withTable(Table $table)
    {
        $clone        = clone $this;
        $clone->table = $table;

        return $clone;
    }

    /**
     * @param string $columnName
     *
     * @return string|null
     */
    public function getPropertyLinkedToColumn($columnName)
    {
        $columnName = array_search($columnName, $this->propertyColumnNameMap, true);

        return $columnName ? $columnName : null;
    }
}