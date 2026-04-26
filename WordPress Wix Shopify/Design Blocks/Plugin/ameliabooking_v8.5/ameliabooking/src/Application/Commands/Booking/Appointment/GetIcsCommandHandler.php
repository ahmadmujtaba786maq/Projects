<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\IcsApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;
use UnexpectedValueException;

/**
 * Class GetIcsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetIcsCommandHandler extends CommandHandler
{
    /**
     * @param GetIcsCommand $command
     *
     * @return CommandResult
     * @throws UnexpectedValueException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws QueryExecutionException
     */
    public function handle(GetIcsCommand $command)
    {
        $result = new CommandResult();

        /** @var IcsApplicationService $icsService */
        $icsService = $this->container->get('application.ics.service');

        $result->setAttachment(true);

        if (!$command->getField('params')['token']) {
            $result->setResult(CommandResult::RESULT_ERROR);

            return $result;
        }

        $result->setFile(
            $icsService->getIcsData(
                $command->getField('params')['type'],
                $command->getArg('id'),
                !empty($command->getField('params')['recurring']) ?
                    $command->getField('params')['recurring'] : [],
                false,
                $command->getField('params')['token']
            )['translated'][0]
        );

        return $result;
    }
}
