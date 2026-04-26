<?php

namespace AmeliaBooking\Infrastructure\Services\Apple;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Common\Container;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;

class StarterAppleCalendarCalendarService extends AbstractAppleCalendarService
{
    /**
     * StarterAppleCalendarCalendarService constructor.
     *
     * @param Container $container
     *
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Check if the Apple ID and app-specific password are correct
     *
     * @param $appleId
     * @param $appSpecificPassword
     *
     * @return string|bool
     */
    public function handleAppleCredentials($appleId, $appSpecificPassword)
    {
        return false;
    }

    /**
     * @param string $appleId
     * @param string $password
     *
     * @return bool|string
     */
    public function getCalendarsUrl($appleId, $password)
    {
        return false;
    }

    /**
     * Returns apple calendar list
     *
     * @param string $appleId
     * @param string $appSpecificPassword
     *
     * @return array
     */
    public function getCalendars($appleId, $appSpecificPassword)
    {
        return [];
    }

    /**
     * Create fake appointments in provider's list so that these slots will not be available for booking
     *
     * @param Collection $providers
     * @param int        $excludeAppointmentId
     * @param DateTime   $startDateTime
     * @param DateTime   $endDateTime
     *
     * @throws Exception
     * @throws ContainerException
     */
    public function removeSlotsFromAppleCalendar(
        $providers,
        $excludeAppointmentId,
        $startDateTime,
        $endDateTime
    ) {
    }

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
    public function getCalendarEvents(
        $appleId,
        $appSpecificPassword,
        $excludeAppointmentId,
        $calendarId,
        $startDateTime,
        $endDateTime,
        $provider
    ) {
        return [];
    }

    /**
     * Handle Apple Calendar Event's.
     *
     * @param Appointment|Event $appointment
     * @param string           $commandSlug
     *
     * @return void
     * @throws ContainerException
     */
    public function handleEvent($appointment, $commandSlug)
    {
    }

    /**
     * @param string $eventId
     * @param string $appleId
     * @param string $appSpecificPassword
     * @param Provider $provider
     *
     * @return string
     */
    public function getAddEventUrl(
        $eventId,
        $appleId,
        $appSpecificPassword,
        Provider $provider
    ) {
        return '';
    }

    /**
     * @param Event $event
     * @param string $commandSlug
     * @param Collection $periods
     * @param null $newProviders
     * @param null $removeProviders
     *
     * @return void
     */
    public function handleEventPeriod(
        $event,
        $commandSlug,
        $periods,
        $newProviders = null,
        $removeProviders = null
    ) {
    }
}
