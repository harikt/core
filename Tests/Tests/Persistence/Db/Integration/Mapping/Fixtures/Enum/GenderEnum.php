<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\Enum;

use Iddigital\Cms\Core\Model\Object\Enum;
use Iddigital\Cms\Core\Model\Object\PropertyTypeDefiner;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GenderEnum extends Enum
{
    const MALE = 'male';
    const FEMALE = 'female';

    public static function male()
    {
        return new self(self::MALE);
    }

    public static function female()
    {
        return new self(self::FEMALE);
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