<?php

namespace AmeliaBooking\Application\Controller\Google;

use AmeliaBooking\Application\Commands\Google\FetchAccessTokenWithAuthCodeCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class FetchAccessTokenWithAuthCodeController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class FetchAccessTokenWithAuthCodeController extends Controller
{
    /**
     * Fields that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'authCode',
        'userId',
        'redirectUri',
        'isBackend'
    ];

    /**
     * Instantiates the FetchAccessTokenWithAuthCodeCommand to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return FetchAccessTokenWithAuthCodeCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new FetchAccessTokenWithAuthCodeCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
