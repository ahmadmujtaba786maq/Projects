<?php

namespace AmeliaBooking\Application\Controller\Square;

use AmeliaBooking\Application\Commands\Square\SquareRefundWebhookCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class SquareRefundWebhookController
 *
 * @package AmeliaBooking\Application\Controller\Square
 */
class SquareRefundWebhookController extends Controller
{
    /**
     * Fields for Square payment that can be received from webhook
     *
     * @var array
     */
    protected $allowedFields = [
        'data'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return SquareRefundWebhookCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new SquareRefundWebhookCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
