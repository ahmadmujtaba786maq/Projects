<?php

namespace AmeliaBooking\Application\Controller\CustomField;

use AmeliaBooking\Application\Commands\CustomField\AddCustomFieldCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class AddCustomFieldController
 *
 * @package AmeliaBooking\Application\Controller\CustomField
 */
class AddCustomFieldController extends Controller
{
    /**
     * Fields for user that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'customField',
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return AddCustomFieldCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddCustomFieldCommand($args);

        $requestBody = $request->getParsedBody();

        $this->filter($requestBody);
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
