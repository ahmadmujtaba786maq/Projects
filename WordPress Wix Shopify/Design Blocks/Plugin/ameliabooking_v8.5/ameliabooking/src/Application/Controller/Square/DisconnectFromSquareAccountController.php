<?php

namespace AmeliaBooking\Application\Controller\Square;

use AmeliaBooking\Application\Commands\Square\DisconnectFromSquareAccountCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class DisconnectFromSquareAccountController
 *
 * @package AmeliaBooking\Application\Controller\Square
 */
class DisconnectFromSquareAccountController extends Controller
{
    /**
     * Fields that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'data'
    ];


    /**
     * @param Request $request
     * @param         $args
     *
     * @return DisconnectFromSquareAccountCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DisconnectFromSquareAccountCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        return $command;
    }
}
