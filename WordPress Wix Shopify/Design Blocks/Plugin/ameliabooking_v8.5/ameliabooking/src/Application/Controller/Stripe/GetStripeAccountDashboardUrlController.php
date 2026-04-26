<?php

namespace AmeliaBooking\Application\Controller\Stripe;

use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Application\Commands\Stripe\GetStripeAccountDashboardUrlCommand;
use Slim\Http\Request;

class GetStripeAccountDashboardUrlController extends Controller
{
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetStripeAccountDashboardUrlCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
