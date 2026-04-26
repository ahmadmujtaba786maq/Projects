<?php

namespace AmeliaBooking\Application\Controller\Test;

use AmeliaBooking\Application\Commands\Test\TestCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class TestController
 *
 * @package AmeliaBooking\Application\Controller\Test
 */
class TestController extends Controller
{
    /**
     * Fields that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'entitiesIds',
    ];

    /**
     * Instantiates the Test command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return TestCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new TestCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
