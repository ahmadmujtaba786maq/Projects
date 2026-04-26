<?php

namespace AmeliaBooking\Application\Controller\CustomField;

use AmeliaBooking\Application\Commands\CustomField\DeleteCustomFieldCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class DeleteCustomFieldController
 *
 * @package AmeliaBooking\Application\Controller\CustomField
 */
class DeleteCustomFieldController extends Controller
{
    /**
     * @param Request $request
     * @param         $args
     *
     * @return DeleteCustomFieldCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeleteCustomFieldCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
