<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\Enum;

use Dms\Core\Model\Object\Enum;
use Dms\Core\Model\Object\PropertyTypeDefiner;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class StatusEnum extends Enum
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';

    public static function active()
    {
        return new self(self::ACTIVE);
    }

    public static function inactive()
    {
        return new self(self::INACTIVE);
    }

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