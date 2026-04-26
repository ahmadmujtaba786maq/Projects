<?php

namespace AmeliaBooking\Application\Commands\Square;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\Services\Payment\SquareService;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetSquareAuthURLCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Square
 */
class GetSquareAuthURLCommandHandler extends CommandHandler
{
    /**
     * @param GetSquareAuthURLCommand $command
     *
     * @return CommandResult
     * @throws ContainerException
     */
    public function handle(GetSquareAuthURLCommand $command)
    {
        $result = new CommandResult();

        /** @var SquareService $squareService */
        $squareService = $this->container->get('infrastructure.payment.square.service');

        $authUrl = $squareService->getAuthUrl();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved square authorization URL');
        $result->setData(
            [
            'authUrl' => filter_var($authUrl, FILTER_SANITIZE_URL)
            ]
        );

        return $result;
    }
}
