<?php declare(strict_types = 1);

namespace Dms\Core\Form\Field\Processor;

use Dms\Core\Model\Type\Builder\Type;

/**
 * The trim processor.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class TrimProcessor extends FieldProcessor
{
    /**
     * @var string
     */
    private $characters;

    public function __construct($characters)
    {
        parent::__construct(Type::string());
        $this->characters = $characters;
    }

    protected function doProcess($input, array &$messages)
    {
        if ($this->characters) {
            return trim($input, $this->characters);
        } else {
            return trim($input);
        }
    }

    protected function doUnprocess($input)
    {
        return $input;
    }
}