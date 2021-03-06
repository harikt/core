<?php declare(strict_types = 1);

namespace Dms\Core\Form\Processor;

use Dms\Core\Form\IFormProcessor;
use Dms\Core\Language\Message;

/**
 * The form processor base class.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
abstract class FormProcessor implements IFormProcessor
{
    /**
     * {@inheritDoc}
     */
    public function process(array $input, array &$messages) : array
    {
        return $this->doProcess($input, $messages);
    }

    /**
     * {@inheritDoc}
     */
    public function unprocess(array $input) : array
    {
        return $this->doUnprocess($input);
    }

    /**
     * @param array     $input
     * @param Message[] $messages
     *
     * @return array
     */
    abstract protected function doProcess(array $input, array &$messages) : array;

    /**
     * @param array $input
     *
     * @return array
     */
    abstract protected function doUnprocess(array $input) : array;
}
