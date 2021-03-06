<?php declare(strict_types = 1);

namespace Dms\Core\Form\Field\Builder;

use Dms\Core\Form\Field\Processor\DateTimeProcessor;
use Dms\Core\Form\Field\Type\DateTimeTypeBase;

/**
 * The date time field builder class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class DateFieldBuilder extends FieldBuilderBase
{
    /**
     * @var DateTimeTypeBase
     */
    protected $type;

    /**
     * DateFieldBuilder constructor.
     *
     * @param DateTimeTypeBase $type
     * @param FieldBuilderBase $previous
     */
    public function __construct(DateTimeTypeBase $type, FieldBuilderBase $previous)
    {
        parent::__construct($previous);
        $this->type = $type;
    }

    /**
     * Validates the date time is greater than or equal to
     * the supplied date time
     *
     * @param \DateTimeInterface $min
     *
     * @return static
     */
    public function min(\DateTimeInterface $min)
    {
        $min = $this->processDateTime($min);

        return $this->attr(DateTimeTypeBase::ATTR_MIN, $min);
    }

    /**
     * Validates the date time is greater than the supplied date time
     *
     * @param \DateTimeInterface $value
     *
     * @return static
     */
    public function greaterThan(\DateTimeInterface $value)
    {
        $value = $this->processDateTime($value);

        return $this->attr(DateTimeTypeBase::ATTR_GREATER_THAN, $value);
    }

    /**
     * Validates the date time is less than or equal to
     * the supplied date time
     *
     * @param \DateTimeInterface $max
     *
     * @return static
     */
    public function max(\DateTimeInterface $max)
    {
        $max = $this->processDateTime($max);

        return $this->attr(DateTimeTypeBase::ATTR_MAX, $max);
    }

    /**
     * Validates the date time is greater than the supplied date time
     *
     * @param \DateTimeInterface $value
     *
     * @return static
     */
    public function lessThan(\DateTimeInterface $value)
    {
        $value = $this->processDateTime($value);

        return $this->attr(DateTimeTypeBase::ATTR_LESS_THAN, $value);
    }

    /**
     * @param \DateTimeInterface $value
     *
     * @return \DateTimeImmutable
     */
    protected function processDateTime(\DateTimeInterface $value)
    {
        $newDateTime = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $value->format('Y-m-d H:i:s'),
                $value->getTimezone()
        );

        return DateTimeProcessor::zeroUnusedParts($this->type->getMode(), $newDateTime);
    }

    /**
     * @inheritDoc
     */
    protected function processDefaultValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $this->processDateTime($value);
        } else {
            return parent::processDefaultValue($value);
        }
    }
}