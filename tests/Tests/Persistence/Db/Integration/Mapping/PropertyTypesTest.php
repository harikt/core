<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping;

use Dms\Core\Persistence\Db\Mapping\CustomOrm;
use Dms\Core\Persistence\Db\Mapping\IEntityMapper;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\PropertyTypes\PropertyTypesEntity;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\PropertyTypes\PropertyTypesMapper;

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
        $this->setDataInDb([
                'property_types' => [
                        ['id' => 1, 'value' => 'aBc', 'value_upper' => 'ABC', 'value_lower' => 'abc']
                ]
        ]);

        $entity = $this->repo->get(1);
        $this->assertEquals(new PropertyTypesEntity(1, 'aBc'), $entity);
    }

    public function testLoadPartial()
    {
        $this->setDataInDb([
                'property_types' => [
                        ['id' => 1, 'value' => 'aBc', 'value_upper' => 'ABC', 'value_lower' => 'abc']
                ]
        ]);


        $this->assertEquals([['id' => 1, 'val' => 'aBc']], $this->repo->loadMatching(
                $this->repo->loadCriteria()
                        ->loadAll(['id', 'value' => 'val'])
        ));
    }
}