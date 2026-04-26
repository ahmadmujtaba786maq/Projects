<?php

namespace AmeliaBooking\Application\Controller\Mailchimp;

use AmeliaBooking\Application\Commands\Mailchimp\FetchAccessTokenMailchimpCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class FetchAccessTokenMailchimpController
 *
 * @package AmeliaBooking\Application\Controller\Mailchimp
 */
class FetchAccessTokenMailchimpController extends Controller
{
    /**
     * Fields that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'access_token',
        'error'
    ];

    /**
     * Instantiates the FetchAccessTokenMailchimpCommand to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return FetchAccessTokenMailchimpCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new FetchAccessTokenMailchimpCommand($args);

        $this->setCommandFields($command, $request->getQueryParams());

        return $command;
    }
}
