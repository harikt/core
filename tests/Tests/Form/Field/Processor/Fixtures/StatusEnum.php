<?php

namespace Dms\Core\Tests\Form\Field\Processor\Fixtures;

use Dms\Core\Model\Object\Enum;
use Dms\Core\Model\Object\PropertyTypeDefiner;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class StatusEnum extends Enum
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';

    /**
     * Defines the type of options contained within the enum.
     *
     * @param PropertyTypeDefiner $values
     *
     * @return void
     */
    protected function defineEnumValues(PropertyTypeDefiner $values)
    {
        $values->asString();
    }
}