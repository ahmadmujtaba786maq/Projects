<?php

namespace AmeliaBooking\Infrastructure\Services\Google;

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

/**
 * Class AbstractGoogleCalendarService
 *
 * @package AmeliaBooking\Infrastructure\Services\Google
 */
abstract class AbstractGoogleCalendarService
{
    /** @var Container $container */
    protected $container;

    public static $providersGoogleEvents = [];

    /**
     * Create a URL to obtain user authorization.
     *
     * @param $providerId
     * @param $redirectUri
     *
     * @return string
     */
    abstract public function createAuthUrl($providerId, $redirectUri);

    /**
     * Exchange a code for a valid authentication token.
     *
     * @param $authCode
     * @param $redirectUri
     * @return string
     */
    abstract public function fetchAccessTokenWithAuthCode($authCode, $redirectUri);

    /**
     * Returns entries on the user's calendar list.
     *
     * @param Provider $provider
     *
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    abstract public function listCalendarList($provider);

    /**
     * Get Provider's Google Calendar ID.
     *
     * @param Provider $provider
     *
     * @return null|string
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    abstract public function getProviderGoogleCalendarId($provider);

    /**
     * Handle Google Calendar Event's.
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
     * Handle Google Calendar Events.
     *
     * @param Event $event
     * @param string $commandSlug
     * @param Collection $periods
     * @param array $providers
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    abstract public function handleEventPeriodsChange($event, $commandSlug, $periods, $providers = null, $providersRemove = null);

    /**
     * Get providers events within date range
     *
     * @param array $providerArr
     * @param string $dateStart
     * @param string $dateStartEnd
     * @param string $dateEnd
     * @param array $eventIds
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    abstract public function getEvents($providerArr, $dateStart, $dateStartEnd, $dateEnd, $eventIds);

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
    abstract public function removeSlotsFromGoogleCalendar(
        $providers,
        $excludeAppointmentId,
        $startDateTime,
        $endDateTime
    );
}
