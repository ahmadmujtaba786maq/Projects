<?php

namespace AmeliaBooking\Infrastructure\Services\Apple;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;

abstract class AbstractAppleCalendarService
{
    /** @var Container $container */
    protected $container;

    public static $providersAppleEvents = [];

    /**
     * Check if the Apple ID and app-specific password are correct
     *
     * @param $appleId
     * @param $appSpecificPassword
     *
     * @return string|bool
     */
    abstract public function handleAppleCredentials($appleId, $appSpecificPassword);

    /**
     * @param string $appleId
     * @param string $password
     *
     * @return bool|string
     */
    abstract public function getCalendarsUrl($appleId, $password);

    /**
     * Returns apple calendar list
     *
     * @param string $appleId
     * @param string $appSpecificPassword
     *
     * @return array
     */
    abstract public function getCalendars($appleId, $appSpecificPassword);

    /**
     * Create fake appointments in provider's list so that these slots will not be available for booking
     *
     * @param Collection $providers
     * @param int        $excludeAppointmentId
     * @param DateTime   $startDateTime
     * @param DateTime   $endDateTime
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    abstract public function removeSlotsFromAppleCalendar(
        $providers,
        $excludeAppointmentId,
        $startDateTime,
        $endDateTime
    );

    /**
     * Get providers calendar events within date range
     *
     * @param $appleId
     * @param $appSpecificPassword
     * @param $excludeAppointmentId
     * @param $calendarId
     * @param $startDateTime
     * @param $endDateTime
     * @param $provider
     *
     * @return array
     */
    abstract public function getCalendarEvents(
        $appleId,
        $appSpecificPassword,
        $excludeAppointmentId,
        $calendarId,
        $startDateTime,
        $endDateTime,
        $provider
    );

    /**
     * Handle Apple Calendar Event's.
     *
     * @param Appointment|Event $appointment
     * @param string           $commandSlug
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    abstract public function handleEvent($appointment, $commandSlug);

    /**
     * @param string $eventId
     * @param string $appleId
     * @param string $appSpecificPassword
     * @param Provider $provider
     *
     * @return string
     */
    abstract public function getAddEventUrl(
        $eventId,
        $appleId,
        $appSpecificPassword,
        Provider $provider
    );

    /**
     * @param Event $event
     * @param string $commandSlug
     * @param Collection $periods
     *
     * @return void
     * @throws ContainerException
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    abstract public function handleEventPeriod(
        $event,
        $commandSlug,
        $periods,
        $newProviders = null,
        $removeProviders = null
    );
}
