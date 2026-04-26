<?php

namespace AmeliaBooking\Application\Controller\Outlook;

use AmeliaBooking\Application\Commands\Outlook\FetchAccessTokenWithAuthCodeOutlookCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class FetchAccessTokenWithAuthCodeOutlookController
 *
 * @package AmeliaBooking\Application\Controller\Outlook
 */
class FetchAccessTokenWithAuthCodeOutlookController extends Controller
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
    ];

    /**
     * Instantiates the FetchAccessTokenWithAuthCodeOutlookCommand to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return FetchAccessTokenWithAuthCodeOutlookCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new FetchAccessTokenWithAuthCodeOutlookCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
