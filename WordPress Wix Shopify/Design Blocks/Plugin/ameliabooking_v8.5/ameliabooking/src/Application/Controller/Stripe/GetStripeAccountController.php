<?php

namespace AmeliaBooking\Application\Controller\Stripe;

use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Application\Commands\Stripe\GetStripeAccountCommand;
use Slim\Http\Request;

class GetStripeAccountController extends Controller
{
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetStripeAccountCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
