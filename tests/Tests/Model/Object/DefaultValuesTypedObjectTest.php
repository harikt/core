<?php

namespace Dms\Core\Tests\Model\Object;

use Dms\Core\Tests\Model\Object\Fixtures\DefaultPropertyValues;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class TypedObjectDefaultValuesTest extends TypedObjectTest
{
    /**
     * @var DefaultPropertyValues
     */
    protected $object;

    /**
     * @return DefaultPropertyValues
     */
    protected function buildObject()
    {
        return DefaultPropertyValues::build();
    }

    public function testDefaultValues()
    {
        $this->assertSame(['abc'], $this->object->one);
        $this->assertSame('bar', $this->object->foo);
        $this->assertSame(123.4, $this->object->number);
    }
}