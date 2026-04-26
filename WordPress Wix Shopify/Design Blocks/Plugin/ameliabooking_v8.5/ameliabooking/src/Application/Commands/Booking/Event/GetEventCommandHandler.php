<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetEventCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetEventCommandHandler extends CommandHandler
{
    /**
     * @param GetEventCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function handle(GetEventCommand $command)
    {
        $result = new CommandResult();

        try {
            /** @var AbstractUser $user */
            $user = $command->getUserApplicationService()->authorization(
                $command->getPage() === 'cabinet' ? $command->getToken() : null,
                $command->getCabinetType()
            );
        } catch (AuthorizationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData(
                [
                    'reauthorize' => true
                ]
            );

            return $result;
        }

        if ($user === null) {
            throw new AccessDeniedException('You are not allowed to read events');
        }

        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');

        /** @var Event $event */
        $event = $eventApplicationService->getEventById(
            (int)$command->getField('id'),
            [
                'fetchEventsPeriods'    => true,
                'fetchEventsTickets'    => true,
                'fetchEventsTags'       => true,
                'fetchEventsProviders'  => true,
                'fetchEventsImages'     => true,
                'fetchBookings'         => true,
                'fetchBookingsTickets'  => true,
                'fetchBookingsUsers'    => true,
                'fetchBookingsPayments' => true,
                'fetchBookingsCoupons'  => true,
            ]
        );

        if (!$event) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not retrieve event');
            $result->setData(
                [
                    Entities::EVENT => []
                ]
            );

            return $result;
        }

        // set tickets price by dateRange
        if ($event->getCustomTickets()->getItems()) {
            /** @var EventApplicationService $eventAS */
            $eventAS = $this->container->get('application.booking.event.service');

            $event->setCustomTickets($eventAS->getTicketsPriceByDateRange($event->getCustomTickets()));
        }


        if (!empty($command->getField('params')['timeZone'])) {
            /** @var EventPeriod $period */
            foreach ($event->getPeriods()->getItems() as $period) {
                $period->getPeriodStart()->getValue()->setTimezone(
                    new \DateTimeZone($command->getField('params')['timeZone'])
                );

                $period->getPeriodEnd()->getValue()->setTimezone(
                    new \DateTimeZone($command->getField('params')['timeZone'])
                );
            }
        }

        /** @var CustomerApplicationService $customerAS */
        $customerAS = $this->container->get('application.user.customer.service');

        $customerAS->removeBookingsForOtherCustomers($user, new Collection([$event]));

        $eventArray = $event->toArray();

        $eventArray = apply_filters('amelia_get_event_filter', $eventArray);

        do_action('amelia_get_event', $eventArray);


        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved event');
        $result->setData(
            [
                Entities::EVENT => $eventArray
            ]
        );

        return $result;
    }
}
