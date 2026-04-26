<?php

namespace AmeliaBooking\Application\Controller\User\Authentication;

use AmeliaBooking\Application\Commands\User\SocialLoginCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

class SocialLoginController extends Controller
{
    /**
     * Fields for social login that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'code',
        'cabinetType',
        'redirectUri',
    ];

    protected function instantiateCommand(Request $request, $args)
    {
        $command = new SocialLoginCommand($args);

        $parsedBody = $request->getParsedBody();

        $this->setCommandFields($command, $parsedBody);

        return $command;
    }
}
