<?php

namespace AmeliaBooking\Infrastructure\Services\Google;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Common\Container;
use DateTime;

/**
 * Class StarterGoogleCalendarService
 *
 * @package AmeliaBooking\Infrastructure\Services\Google
 */
class StarterGoogleCalendarService extends AbstractGoogleCalendarService
{
    /**
     * StarterGoogleCalendarService constructor.
     *
     * @param Container $container
     *
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create a URL to obtain user authorization.
     *
     * @param $providerId
     * @param $redirectUri
     *
     * @return string
     */
    public function createAuthUrl($providerId, $redirectUri)
    {
        return '';
    }

    /**
     * Exchange a code for a valid authentication token.
     *
     * @param $authCode
     * @param $redirectUri
     *
     * @return string
     */
    public function fetchAccessTokenWithAuthCode($authCode, $redirectUri)
    {
        return '';
    }

    /**
     * Returns entries on the user's calendar list.
     *
     * @param Provider $provider
     *
     * @return array
     */
    public function listCalendarList($provider)
    {
        return [];
    }

    /**
     * Get Provider's Google Calendar ID.
     *
     * @param Provider $provider
     *
     * @return null|string
     */
    public function getProviderGoogleCalendarId($provider)
    {
        return null;
    }

    /**
     * Handle Google Calendar Event's.
     *
     * @param Appointment|Event $appointment
     * @param string            $commandSlug
     *
     * @return void
     */
    public function handleEvent($appointment, $commandSlug)
    {
    }

    /**
     * Handle Google Calendar Events.
     *
     * @param Event      $event
     * @param string     $commandSlug
     * @param Collection $periods
     * @param array      $providers
     *
     * @return void
     */
    public function handleEventPeriodsChange($event, $commandSlug, $periods, $providers = null, $providersRemove = null)
    {
    }

    /**
     * Get providers events within date range
     *
     * @param array  $providerArr
     * @param string $dateStart
     * @param string $dateStartEnd
     * @param string $dateEnd
     * @param array  $eventIds
     *
     * @return array
     */
    public function getEvents($providerArr, $dateStart, $dateStartEnd, $dateEnd, $eventIds)
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
     * @return void
     */
    public function removeSlotsFromGoogleCalendar(
        $providers,
        $excludeAppointmentId,
        $startDateTime,
        $endDateTime
    ) {
    }
}
