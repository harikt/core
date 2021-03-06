<?php declare(strict_types = 1);

namespace Dms\Core\Table\Data;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Table\IDataTable;
use Dms\Core\Table\ITableSection;
use Dms\Core\Table\ITableStructure;

/**
 * The table class
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class DataTable implements IDataTable
{
    /**
     * @var ITableStructure
     */
    protected $structure;

    /**
     * @var ITableSection[]
     */
    protected $sections;

    /**
     * Table constructor.
     *
     * @param ITableStructure      $structure
     * @param ITableSection[]      $sections
     *
     * @throws InvalidArgumentException
     */
    public function __construct(ITableStructure $structure, array $sections)
    {
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'sections', $sections, ITableSection::class);
        $this->structure          = $structure;
        $this->sections           = $sections;

        foreach ($sections as $section) {
            if ($section->getStructure() !== $structure) {
                throw InvalidArgumentException::format(
                        'Invalid section supplied to %s: section table structure is not equal to the parent table structure',
                        __METHOD__
                );
            }
        }
    }

    /**
     * @return ITableStructure
     */
    final public function getStructure() : \Dms\Core\Table\ITableStructure
    {
        return $this->structure;
    }

    /**
     * @return ITableSection[]
     */
    final public function getSections() : array
    {
        return $this->sections;
    }
}