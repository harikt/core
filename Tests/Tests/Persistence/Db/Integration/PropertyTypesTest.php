<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration;

use Iddigital\Cms\Core\Persistence\Db\Mapping\CustomOrm;
use Iddigital\Cms\Core\Persistence\Db\Mapping\IEntityMapper;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\PropertyTypes\PropertyTypesEntity;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\PropertyTypes\PropertyTypesMapper;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PropertyTypesTest extends DbIntegrationTest
{
    /**
     * @inheritDoc
     */
    protected function loadOrm()
    {
        return CustomOrm::from([PropertyTypesEntity::class => PropertyTypesMapper::class]);
    }

    public function testPersist()
    {
        $entity = new PropertyTypesEntity(null, 'aBc');

        $this->repo->save($entity);

        $this->assertDatabaseDataSameAs([
            'property_types' => [
                ['id' => 1, 'value' => 'aBc', 'value_upper' => 'ABC', 'value_lower' => 'abc']
            ]
        ]);
    }

    public function testLoad()
    {
        $this->db->setData([
                'property_types' => [
                        ['id' => 1, 'value' => 'aBc', 'value_upper' => 'ABC', 'value_lower' => 'abc']
                ]
        ]);

        $entity = $this->repo->get(1);
        $this->assertEquals(new PropertyTypesEntity(1, 'aBc'), $entity);
    }
}