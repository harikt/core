<?php declare(strict_types = 1);

namespace Dms\Core\Module;

use Dms\Core\Auth\AdminForbiddenException;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Exception\InvalidOperationException;
use Dms\Core\Form\InvalidFormSubmissionException;
use Dms\Core\Form\IStagedForm;
use Dms\Core\Model\IDataTransferObject;
use Dms\Core\Persistence;

/**
 * The parameterized action interface.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
interface IParameterizedAction extends IAction
{
    /**
     * Gets the action handler
     *
     * @return IParameterizedActionHandler
     */
    public function getHandler() : IActionHandler;

    /**
     * Gets the form required to run this action.
     *
     * @return IStagedForm
     */
    public function getStagedForm() : IStagedForm;

    /**
     * Runs the action.
     *
     * @param array $data
     *
     * @return object|null
     * @throws AdminForbiddenException if the authenticated user does not have the required permissions.
     * @throws InvalidFormSubmissionException if the form data is invalid
     */
    public function run(array $data);

    /**
     * Runs the action without verifying the user is authorized.
     *
     * @param array $data
     *
     * @return object|null
     * @throws InvalidFormSubmissionException if the form data is invalid
     */
    public function runWithoutAuthorization(array $data);

    /**
     * Returns an equivalent parameterized action with the first stage
     * of the form filled out with the supplied data.
     *
     * @param array $data
     *
     * @return static
     * @throws AdminForbiddenException if the authenticated user does not have the required permissions.
     * @throws InvalidFormSubmissionException if the form data is invalid
     * @throws InvalidOperationException If there is only one stage
     */
    public function submitFirstStage(array $data);

    /**
     * Returns an equivalent parameterized action with the first stage
     * of the form filled out with the supplied processed data.
     *
     * This will *not* process the data through the form and is expected
     * to be in the correct format.
     *
     * @param array $processedData
     *
     * @return static
     * @throws AdminForbiddenException if the authenticated user does not have the required permissions.
     * @throws InvalidArgumentException If processed form data is invalid
     * @throws InvalidOperationException If there is only one stage
     */
    public function withSubmittedFirstStage(array $processedData);
}