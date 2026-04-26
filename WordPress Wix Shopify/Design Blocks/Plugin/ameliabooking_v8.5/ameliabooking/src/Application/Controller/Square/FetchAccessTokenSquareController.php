<?php

namespace AmeliaBooking\Application\Controller\Square;

use AmeliaBooking\Application\Commands\Square\FetchAccessTokenSquareCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class FetchAccessTokenSquareController
 *
 * @package AmeliaBooking\Application\Controller\Square
 */
class FetchAccessTokenSquareController extends Controller
{
    /**
     * Fields that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'access_token',
        'expires_at',
        'refresh_token',
        'merchant_id',
        'decrypted_access_token',
        'decrypted_refresh_token'
    ];

    /**
     * Instantiates the FetchAccessTokenSquareCommand to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return FetchAccessTokenSquareCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new FetchAccessTokenSquareCommand($args);

        $this->setCommandFields($command, $request->getQueryParams());

        return $command;
    }
}
