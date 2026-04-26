<?php

namespace AmeliaBooking\Application\Controller\CustomField;

use AmeliaBooking\Application\Commands\CustomField\UpdateCustomFieldsPositionsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateCustomFieldsPositionsController
 *
 * @package AmeliaBooking\Application\Controller\CustomField
 */
class UpdateCustomFieldsPositionsController extends Controller
{
    /**
     * Fields for custom fields that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'customFields'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return UpdateCustomFieldsPositionsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateCustomFieldsPositionsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
