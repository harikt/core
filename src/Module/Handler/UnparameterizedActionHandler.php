<?php declare(strict_types = 1);

namespace Dms\Core\Module\Handler;

use Dms\Core\Form;
use Dms\Core\Model\IDataTransferObject;
use Dms\Core\Module\IUnparameterizedActionHandler;

/**
 * The action handler base class.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
abstract class UnparameterizedActionHandler extends ActionHandler implements IUnparameterizedActionHandler
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct($this->getReturnType());
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        return $this->verifyResult($this->handle());
    }

    /**
     * Gets the return dto type of the action handler.
     *
     * @return string|null
     */
    abstract protected function getReturnType();

    /**
     * Runs the action handler
     *
     * @return object|null
     */
    abstract public function handle();
}