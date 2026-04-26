<?php

namespace AmeliaBooking\Application\Controller\Stripe;

use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Application\Commands\Stripe\StripeAccountDisconnectCommand;
use Slim\Http\Request;

class StripeAccountDisconnectController extends Controller
{
    /**
     * Fields for stripe connect that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [];

    protected function instantiateCommand(Request $request, $args)
    {
        $command = new StripeAccountDisconnectCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
