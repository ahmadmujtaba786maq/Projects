<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Resource;

use AmeliaBooking\Application\Commands\Bookable\Resource\UpdateResourceCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateResourceController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Resource
 */
class UpdateResourceController extends Controller
{
    /**
     * Fields for resource that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'name',
        'quantity',
        'status',
        'shared',
        'entities',
        'countAdditionalPeople'
    ];

    /**
     * Instantiates the Update Resource command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateResourceCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateResourceCommand($args);

        $command->setField('id', (int)$command->getArg('id'));

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
