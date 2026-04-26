<?php

namespace AmeliaBooking\Application\Controller\Activation;

use AmeliaBooking\Application\Commands\Activation\ParseDomainCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class ParseDomainController
 *
 * @package AmeliaBooking\Application\Controller\Activation
 */
class ParseDomainController extends Controller
{
    /**
     * Fields for appointment that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'domain',
        'subdomain'
    ];

    /**
     * Instantiates the Activate Plugin command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return ParseDomainCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new ParseDomainCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
