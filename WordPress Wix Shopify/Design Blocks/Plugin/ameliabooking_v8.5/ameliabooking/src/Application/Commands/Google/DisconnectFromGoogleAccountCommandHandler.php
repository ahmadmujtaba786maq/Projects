<?php

namespace AmeliaBooking\Application\Commands\Google;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\Google\GoogleCalendar;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Google\GoogleCalendarRepository;
use Interop\Container\Exception\ContainerException;

/**
 * Class DisconnectFromGoogleAccountCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Google
 */
class DisconnectFromGoogleAccountCommandHandler extends CommandHandler
{
    /**
     * @param DisconnectFromGoogleAccountCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(DisconnectFromGoogleAccountCommand $command)
    {

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        if (!$command->getPermissionService()->currentUserCanRead(Entities::EMPLOYEES)) {
            try {
                /** @var AbstractUser $user */
                $user = $userAS->authorization(
                    $command->getToken(),
                    Entities::PROVIDER
                );
            } catch (AuthorizationException $e) {
                $result = new CommandResult();
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );
                return $result;
            }

            if ($userAS->isCustomer($user)) {
                throw new AccessDeniedException('You are not allowed');
            }
        }

        $result = new CommandResult();

        /** @var GoogleCalendarRepository $googleCalendarRepository */
        $googleCalendarRepository = $this->container->get('domain.google.calendar.repository');

        $googleCalendar = $googleCalendarRepository->getByProviderId($command->getArg('id'));

        if (!$googleCalendar instanceof GoogleCalendar) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete google calendar.');

            return $result;
        }

        do_action('amelia_before_google_calendar_deleted', $googleCalendar->toArray(), $command->getArg('id'));

        if ($googleCalendarRepository->delete($googleCalendar->getId()->getValue())) {
            do_action('amelia_after_google_calendar_deleted', $googleCalendar, $command->getArg('id'));

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Google calendar successfully deleted.');
        }

        return $result;
    }
}
