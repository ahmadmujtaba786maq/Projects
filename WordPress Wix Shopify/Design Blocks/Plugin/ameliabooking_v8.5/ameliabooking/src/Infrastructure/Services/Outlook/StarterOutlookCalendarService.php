<?php

namespace AmeliaBooking\Infrastructure\Services\Outlook;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Common\Container;
use Interop\Container\Exception\ContainerException;
use WP_Error;

/**
 * Class StarterOutlookCalendarService
 *
 * @package AmeliaBooking\Infrastructure\Services\Outlook
 */
class StarterOutlookCalendarService extends AbstractOutlookCalendarService
{
    /**
     * StarterOutlookCalendarService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create a URL to obtain user authorization.
     *
     * @param $providerId
     *
     * @return string
     *
     * @throws ContainerException
     */
    public function createAuthUrl($providerId)
    {
        return '';
    }

    /**
     * @return void
     */
    public static function handleCallback()
    {
    }

    /**
     * @param $authCode
     * @param $redirectUri
     * @param $providerId
     *
     * @return array
     */
    public function fetchAccessTokenWithAuthCode($authCode, $redirectUri, $providerId)
    {
        return ['outcome' => true, 'result' => []];
    }

    /**
     * @param Provider $provider
     *
     * @return array
     */
    public function listCalendarList($provider)
    {
        return [];
    }

    /**
     * Get Provider's Outlook Calendar ID.
     *
     * @param Provider $provider
     *
     * @return null|string
     */
    public function getProviderOutlookCalendarId($provider)
    {
        return null;
    }

    /**
     * @param Appointment $appointment
     * @param string      $commandSlug
     * @param null|string $oldStatus
     *
     * @return void
     */
    public function handleEvent($appointment, $commandSlug, $oldStatus = null)
    {
    }

    /**
     * @param \AmeliaBooking\Domain\Entity\Booking\Event\Event $event
     * @param string $commandSlug
     * @param Collection $periods
     *
     * @return void
     */
    public function handleEventPeriod($event, $commandSlug, $periods, $newProviders = null, $removeProviders = null)
    {
    }

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
     * @param \DateTime  $startDateTime
     * @param \DateTime  $endDateTime
     *
     * @return void
     */
    public function removeSlotsFromOutlookCalendar(
        $providers,
        $excludeAppointmentId,
        $startDateTime,
        $endDateTime
    ) {
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param string $from
     * @param string $fromName
     * @param string $replyTo
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array  $bccEmails
     * @param array  $attachments
     *
     * @return void
     *
     * @throws ContainerException
     */
    public function sendEmail(
        $from,
        $fromName,
        $replyTo,
        $to,
        $subject,
        $body,
        $bccEmails = [],
        $attachments = []
    ) {
    }
}
