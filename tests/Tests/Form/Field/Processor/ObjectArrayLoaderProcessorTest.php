<?php

namespace Dms\Core\Tests\Form\Field\Processor;

use Dms\Core\Form\Field\Processor\ObjectArrayLoaderProcessor;
use Dms\Core\Form\IFieldProcessor;
use Dms\Core\Model\IEntity;
use Dms\Core\Model\IEntitySet;
use Dms\Core\Model\IIdentifiableObjectSet;
use Dms\Core\Model\Type\Builder\Type;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ObjectArrayLoaderProcessorTest extends FieldProcessorTest
{
    /**
     * @return IFieldProcessor
     */
    protected function processor()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|IIdentifiableObjectSet $entitiesMock */
        $entitiesMock = $this->getMockForAbstractClass(IIdentifiableObjectSet::class);

        $entitiesMock->expects($this->any())
            ->method('getElementType')
            ->willReturn(Type::object(\stdClass::class));
        
        $entitiesMock->expects($this->any())
            ->method('getAllById')
            ->will($this->returnCallback(function (array $ids) {
                $entities = [];

                foreach ($ids as $id) {
                    $entities[] = (object)['id' => $id];
                }

                return $entities;
            }));

        $entitiesMock->expects($this->any())
            ->method('contains')
            ->willReturn(true);

        $entitiesMock->expects($this->any())
            ->method('getObjectId')
            ->will($this->returnCallback(function (IEntity $entity) {
                return $entity->getId();
            }));

        return new ObjectArrayLoaderProcessor($entitiesMock);
    }

    /**
     * @inheritDoc
     */
    protected function processedType()
    {
        return Type::arrayOf(Type::object(\stdClass::class))->nullable();
    }

    /**
     * @return array[]
     */
    public function processTests()
    {
        return [
            [null, null],
            [[1], [(object)['id' => 1]]],
            [[1, 2, 6], [(object)['id' => 1], (object)['id' => 2], (object)['id' => 6]]],
        ];
    }

    /**
     * @return array[]
     */
    public function unprocessTests()
    {
        return [
            [null, null],
            [[$this->entityMock(1)], [1]],
            [[$this->entityMock(1), $this->entityMock(4), $this->entityMock(1234)], [1, 4, 1234]],
        ];
    }

    protected function entityMock($id)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|IEntity $entityMock */
        $entityMock = $this->getMockForAbstractClass(IEntity::class);

        $entityMock->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $entityMock;
    }
}