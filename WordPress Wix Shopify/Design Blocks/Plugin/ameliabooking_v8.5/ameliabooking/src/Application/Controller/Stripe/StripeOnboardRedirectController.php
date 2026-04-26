<?php

namespace AmeliaBooking\Application\Controller\Stripe;

use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Application\Commands\Stripe\StripeOnboardRedirectCommand;
use Slim\Http\Request;

class StripeOnboardRedirectController extends Controller
{
    /**
     * Fields for stripe connect that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'returnUrl',
        'accountType',
    ];

    protected function instantiateCommand(Request $request, $args)
    {
        $command = new StripeOnboardRedirectCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
