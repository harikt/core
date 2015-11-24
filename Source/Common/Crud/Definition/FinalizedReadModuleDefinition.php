<?php

namespace Iddigital\Cms\Core\Common\Crud\Definition;

use Iddigital\Cms\Core\Common\Crud\Table\ISummaryTable;
use Iddigital\Cms\Core\Module\Definition\FinalizedModuleDefinition;

/**
 * The finalized read module definition class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class FinalizedReadModuleDefinition extends FinalizedModuleDefinition
{
    /**
     * @var callable
     */
    private $labelObjectCallback;

    /**
     * @var ISummaryTable
     */
    private $summaryTable;

    /**
     * @inheritDoc
     */
    public function __construct(
            $name,
            callable $labelObjectCallback,
            ISummaryTable $summaryTable,
            array $actions,
            array $tables,
            array $charts,
            array $widgets
    ) {
        parent::__construct($name, $actions, $tables, $charts, $widgets);
        $this->labelObjectCallback = $labelObjectCallback;
        $this->summaryTable = $summaryTable;
    }
}