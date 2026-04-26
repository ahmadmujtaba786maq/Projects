<?php

namespace AmeliaBooking\Application\Controller\Stripe;

use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Application\Commands\Stripe\GetStripeAccountsCommand;
use Slim\Http\Request;

class GetStripeAccountsController extends Controller
{
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetStripeAccountsCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
