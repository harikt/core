<?php

namespace Iddigital\Cms\Core\Tests\Model\Object;

use Iddigital\Cms\Common\Testing\CmsTestCase;
use Iddigital\Cms\Core\Model\IValueObjectCollection;
use Iddigital\Cms\Core\Model\Object\ImmutablePropertyException;
use Iddigital\Cms\Core\Model\Type\Builder\Type;
use Iddigital\Cms\Core\Tests\Model\Object\Fixtures\TestValueObject;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ValueObjectTest extends CmsTestCase
{
    public function testValueObjectCollection()
    {
        $collection = TestValueObject::collection();

        $this->assertInstanceOf(IValueObjectCollection::class, $collection);
        $this->assertSame(TestValueObject::class, $collection->getObjectType());
        $this->assertEquals(Type::object(TestValueObject::class), $collection->getElementType());
    }

    public function testValueObjectsAreImmutableByDefault()
    {
        $object = new TestValueObject();

        $object->one = 'abc';

        $this->setExpectedException(ImmutablePropertyException::class);

        $object->one = '123';
    }
}