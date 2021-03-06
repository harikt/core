<?php declare(strict_types = 1);

namespace Dms\Core\Table\Chart\Structure;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Form\IField;
use Dms\Core\Table\Chart\IChartAxis;
use Dms\Core\Table\Column\Column;
use Dms\Core\Table\Column\Component\ColumnComponent;
use Dms\Core\Table\IColumnComponent;
use Dms\Core\Table\IColumnComponentType;

/**
 * The chart axis class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ChartAxis extends Column implements IChartAxis
{
    /**
     * @var string
     */
    protected static $debugType = 'chart axis';

    /**
     * @var IColumnComponentType
     */
    private $type;

    /**
     * @param string             $name
     * @param string             $label
     * @param IColumnComponent[] $components
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $name, string $label, array $components)
    {
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'components', $components, IColumnComponent::class);
        InvalidArgumentException::verify(!empty($components), 'Components cannot be empty');

        /** @var IColumnComponent $firstComponent */
        $firstComponent = reset($components);
        $this->type     = $firstComponent->getType()->withFieldAs($name, $label);

        foreach ($components as $component) {
            if (!$component->getType()->getPhpType()->equals($this->type->getPhpType())) {
                throw InvalidArgumentException::format(
                        'Invalid component supplied to chart axis \'%s\': expecting component type %s, %s given for component \'%s\'',
                        $name, $this->type->getPhpType()->asTypeString(), $component->getType()->getPhpType()->asTypeString(),
                        $component->getName()
                );
            }

            $this->components[$component->getName()] = $component;
        }

        parent::__construct($name, $label, $hidden = false, $components);
    }

    /**
     * @param IField $field
     *
     * @return ChartAxis
     */
    public static function forField(IField $field) : ChartAxis
    {
        return self::fromComponent(ColumnComponent::forField($field));
    }

    /**
     * @param IColumnComponent $component
     *
     * @return ChartAxis
     */
    public static function fromComponent(IColumnComponent $component) : ChartAxis
    {
        return new self($component->getName(), $component->getLabel(), [$component]);
    }

    /**
     * @return IColumnComponentType
     */
    public function getType() : \Dms\Core\Table\IColumnComponentType
    {
        return $this->type;
    }
}