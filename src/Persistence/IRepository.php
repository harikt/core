<?php declare(strict_types = 1);

namespace Dms\Core\Persistence;

use Dms\Core\Model\ICriteria;
use Dms\Core\Model\IEntity;
use Dms\Core\Model\IEntitySet;
use Dms\Core\Exception;
use Dms\Core\Model\ISpecification;

/**
 * The API for a repository.
 * 
 * The repository acts as an abstraction over the data source for
 * a set of entities.
 * 
 * @author Elliot Levin <elliot@aanet.com.au>
 */
interface IRepository extends IEntitySet
{
    /**
     * Returns the entity type of the repository.
     * 
     * @return string
     */
    public function getEntityType() : string;

    /**
     * {@inheritDoc}
     */
    public function has(int $id) : bool;

    /**
     * {@inheritDoc}
     */
    public function hasAll(array $ids) : bool;

    /**
     * {@inheritDoc}
     */
    public function getAll() : array;

    /**
     * {@inheritDoc}
     */
    public function get(int $id) : \Dms\Core\Model\IEntity;

    /**
     * {@inheritDoc}
     */
    public function getAllById(array $ids) : array;

    /**
     * {@inheritDoc}
     */
    public function tryGet(int $id);

    /**
     * {@inheritDoc}
     */
    public function tryGetAll(array $ids) : array;

    /**
     * {@inheritDoc}
     */
    public function matching(ICriteria $criteria) : array;

    /**
     * {@inheritDoc}
     */
    public function satisfying(ISpecification $specification) : array;
    
    /**
     * Saves the supplied entity to the underlying data source.
     * 
     * @param IEntity $entity
     * @return void
     */
    public function save(IEntity $entity);
    
    /**
     * Saves the supplied entities to the underlying data source.
     * 
     * @param IEntity[] $entities
     * @return void
     */
    public function saveAll(array $entities);

    /**
     * Removes the supplied entity from the underlying data source.
     *
     * @param IEntity $entity
     * @return void
     */
    public function remove(IEntity $entity);

    /**
     * Removes the entity with the supplied id from the underlying data source.
     *
     * @param int $id
     * @return void
     */
    public function removeById(int $id);
    
    /**
     * Removes the supplied entities from the underlying data source.
     * 
     * @param IEntity[] $entities
     * @return void
     */
    public function removeAll(array $entities);

    /**
     * Removes the entities with the supplied ids from the underlying data source.
     *
     * @param int[] $ids
     * @return void
     */
    public function removeAllById(array $ids);

    /**
     * Removes all the entities from the underlying data source.
     *
     * @return void
     */
    public function clear();
}
