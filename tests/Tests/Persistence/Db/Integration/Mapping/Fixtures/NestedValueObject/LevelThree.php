<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\NestedValueObject;

use Dms\Core\Model\Object\ClassDefinition;
use Dms\Core\Model\Object\ValueObject;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class LevelThree extends ValueObject
{
    /**
     * @var string
     */
    public $val;

    /**
     * LevelThree constructor.
     *
     * @param string $val
     */
    public function __construct($val)
    {
        parent::__construct();
        $this->val = $val;
    }

    /**
     * Defines the structure of this class.
     *
     * @param ClassDefinition $class
     */
    protected function define(ClassDefinition $class)
    {
        $class->property($this->val)->asString();
    }
}