<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\ValueObject;

use Iddigital\Cms\Core\Model\Object\Enum;
use Iddigital\Cms\Core\Model\Object\PropertyTypeDefiner;


/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class CurrencyEnum extends Enum
{
    const AUD = 'AUD';
    const USD = 'USD';

    public static function aud()
    {
        return new self(self::AUD);
    }

    public static function usd()
    {
        return new self(self::USD);
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