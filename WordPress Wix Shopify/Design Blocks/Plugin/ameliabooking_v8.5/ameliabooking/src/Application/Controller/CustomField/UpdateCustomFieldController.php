<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\CustomField;

use AmeliaBooking\Application\Commands\CustomField\UpdateCustomFieldCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateCustomFieldController
 *
 * @package AmeliaBooking\Application\Controller\CustomField
 */
class UpdateCustomFieldController extends Controller
{
    /**
     * Fields for custom field that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'id',
        'label',
        'options',
        'position',
        'translations',
        'required',
        'services',
        'events',
        'type',
        'allServices',
        'allEvents',
        'useAsLocation',
        'width',
        'saveType',
        'saveFirstChoice'
    ];

    /**
     * Instantiates the Update Custom Field command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateCustomFieldCommand($args);

        $requestBody = $request->getParsedBody();

        $this->filter($requestBody);
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
