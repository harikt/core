<?php

namespace Iddigital\Cms\Core\Model\Criteria;

use Iddigital\Cms\Core\Exception;
use Iddigital\Cms\Core\Model\IPartialLoadCriteria;

/**
 * The typed object criteria class with the properties of
 * the object to load.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PartialLoadCriteria extends Criteria implements IPartialLoadCriteria
{
    /**
     * @var NestedProperty[]
     */
    private $nestedPropertiesToLoad = [];

    /**
     * Loads the supplied property.
     *
     * @param string      $propertyName
     * @param string|null $loadAsIndex
     *
     * @return static
     * @throws Exception\InvalidOperationException
     */
    final public function load($propertyName, $loadAsIndex = null)
    {
        $this->nestedPropertiesToLoad[$loadAsIndex ?: $propertyName] = NestedProperty::parsePropertyName($this->class, $propertyName);

        return $this;
    }

    /**
     * Load all the supplied properties. Pass an array containing the properties
     * to load as the indexes and the value as the array index to load the property into.
     *
     * Example:
     * <code>
     * ->loadAll([
     *      'some.nested.property' => 'alias-index',
     *      'propertyWithoutIndex',
     * ])
     * </code>
     *
     * @param string[] $propertyNameIndexMap
     *
     * @return static
     * @throws Exception\InvalidOperationException
     */
    final public function loadAll(array $propertyNameIndexMap)
    {
        foreach ($propertyNameIndexMap as $propertyName => $loadAsIndex) {
            if (is_int($propertyName)) {
                $propertyName = $loadAsIndex;
            }

            $this->nestedPropertiesToLoad[$loadAsIndex] = NestedProperty::parsePropertyName($this->class, $propertyName);
        }

        return $this;
    }

    /**
     * @return NestedProperty[]
     */
    final public function getAliasNestedPropertyMap()
    {
        return $this->nestedPropertiesToLoad;
    }

    /**
     * @inheritDoc
     */
    final public function getAliasNestedPropertyNameMap()
    {
        $aliasPropertyNameMap = [];

        foreach ($this->nestedPropertiesToLoad as $alias => $nestedProperty) {
            $aliasPropertyNameMap[$alias] = $nestedProperty->getName();
        }

        return $aliasPropertyNameMap;
    }

}