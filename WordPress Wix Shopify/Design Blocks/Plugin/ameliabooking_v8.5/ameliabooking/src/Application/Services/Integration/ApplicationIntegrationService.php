<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Integration;

use AmeliaBooking\Application\Services\Zoom\AbstractZoomApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Services\Apple\AbstractAppleCalendarService;
use AmeliaBooking\Infrastructure\Services\Google\AbstractGoogleCalendarService;
use AmeliaBooking\Infrastructure\Services\LessonSpace\AbstractLessonSpaceService;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use Interop\Container\Exception\ContainerException;
use Microsoft\Graph\Exception\GraphException;

/**
 * Class ApplicationIntegrationService
 *
 * @package AmeliaBooking\Application\Services\Integration
 */
class ApplicationIntegrationService
{
    public const SKIP_GOOGLE_CALENDAR = 'skipGoogleCalendar';
    public const SKIP_OUTLOOK_CALENDAR = 'skipOutlookCalendar';
    public const SKIP_APPLE_CALENDAR = 'skipAppleCalendar';
    public const SKIP_ZOOM_MEETING = 'skipZoomMeeting';
    public const SKIP_LESSON_SPACE = 'skipLessonSpace';
    public const APPOINTMENT_ADDED = 'appointmentAdded';
    public const APPOINTMENT_EDITED = 'appointmentEdited';
    public const APPOINTMENT_DELETED = 'appointmentDeleted';
    public const APPOINTMENT_STATUS_UPDATED = 'appointmentStatusUpdated';
    public const BOOKING_ADDED = 'bookingAdded';
    public const BOOKING_APPROVED = 'bookingApproved';
    public const BOOKING_CANCELED = 'bookingCanceled';
    public const BOOKING_REJECTED = 'bookingRejected';
    public const BOOKING_STATUS_UPDATED = 'bookingStatusUpdated';
    public const TIME_UPDATED = 'bookingTimeUpdated';
    public const EVENT_ADDED = 'eventAdded';
    public const EVENT_DELETED = 'eventDeleted';
    public const EVENT_PERIOD_ADDED = 'eventPeriodAdded';
    public const EVENT_PERIOD_DELETED = 'eventPeriodDeleted';
    public const EVENT_STATUS_UPDATED = 'eventStatusUpdated';
    public const PROVIDER_CHANGED = 'providerChanged';

    /** @var Container $container */
    protected $container;

    /**
     * ApplicationIntegrationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Appointment $appointment
     * @param array       $appointmentArray
     * @param string      $command
     * @param array       $skippedIntegrations
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws GraphException
     */
    public function handleAppointment($appointment, &$appointmentArray, $command, $skippedIntegrations = [])
    {
        /** @var AbstractGoogleCalendarService $googleCalendarService */
        $googleCalendarService = $this->container->get('infrastructure.google.calendar.service');

        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');

        /** @var AbstractAppleCalendarService $appleCalendarService */
        $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

        /** @var AbstractZoomApplicationService $zoomService */
        $zoomService = $this->container->get('application.zoom.service');

        /** @var AbstractLessonSpaceService $lessonSpaceService */
        $lessonSpaceService = $this->container->get('infrastructure.lesson.space.service');

        if (empty($skippedIntegrations[self::SKIP_ZOOM_MEETING])) {
            $zoomService->handleAppointmentMeeting($appointment, $command);

            if ($appointment->getZoomMeeting()) {
                $appointmentArray['zoomMeeting'] = $appointment->getZoomMeeting()->toArray();
            }
        }


        if (empty($skippedIntegrations[self::SKIP_LESSON_SPACE])) {
            $lessonSpaceService->handle($appointment, Entities::APPOINTMENT);

            if ($appointment->getLessonSpace()) {
                $appointmentArray['lessonSpace'] = $appointment->getLessonSpace();
            }
        }

        if (empty($skippedIntegrations[self::SKIP_GOOGLE_CALENDAR])) {
            $googleCalendarService->handleEvent($appointment, $command);

            if ($appointment->getGoogleCalendarEventId() !== null) {
                $appointmentArray['googleCalendarEventId'] = $appointment->getGoogleCalendarEventId()->getValue();
            }

            if ($appointment->getGoogleMeetUrl() !== null) {
                $appointmentArray['googleMeetUrl'] = $appointment->getGoogleMeetUrl();
            }
        }

        if (empty($skippedIntegrations[self::SKIP_OUTLOOK_CALENDAR])) {
            $outlookCalendarService->handleEvent($appointment, $command);

            if ($appointment->getOutlookCalendarEventId() !== null) {
                $appointmentArray['outlookCalendarEventId'] = $appointment->getOutlookCalendarEventId()->getValue();
            }

            if ($appointment->getMicrosoftTeamsUrl() !== null) {
                $appointmentArray['microsoftTeamsUrl'] = $appointment->getMicrosoftTeamsUrl();
            }
        }

        if (empty($skippedIntegrations[self::SKIP_APPLE_CALENDAR])) {
            $appleCalendarService->handleEvent($appointment, $command);

            if ($appointment->getAppleCalendarEventId() !== null) {
                $appointmentArray['appleCalendarEventId'] = $appointment->getAppleCalendarEventId()->getValue();
            }
        }
    }

    /**
     * @param Appointment $appointment
     * @param array       $appointmentArray
     * @param int|null    $oldProviderId
     *
     * @throws ContainerException
     * @throws GraphException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public function handleAppointmentEmployeeChange($appointment, &$appointmentArray, $oldProviderId)
    {
        /** @var AbstractGoogleCalendarService $googleCalendarService */
        $googleCalendarService = $this->container->get('infrastructure.google.calendar.service');

        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');

        /** @var AbstractAppleCalendarService $appleCalendarService */
        $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

        if ($oldProviderId) {
            $newProviderId = $appointment->getProviderId()->getValue();

            $appointment->setProviderId(new Id($oldProviderId));

            $googleCalendarService->handleEvent($appointment, self::APPOINTMENT_DELETED);

            $appointment->setGoogleCalendarEventId(null);

            $outlookCalendarService->handleEvent($appointment, self::APPOINTMENT_DELETED);

            $appointment->setOutlookCalendarEventId(null);

            $appleCalendarService->handleEvent($appointment, self::APPOINTMENT_DELETED);

            $appointment->setAppleCalendarEventId(null);

            $appointment->setProviderId(new Id($newProviderId));

            $googleCalendarService->handleEvent($appointment, self::APPOINTMENT_ADDED);

            $outlookCalendarService->handleEvent($appointment, self::APPOINTMENT_ADDED);

            $appleCalendarService->handleEvent($appointment, self::APPOINTMENT_ADDED);
        } else {
            $googleCalendarService->handleEvent($appointment, self::APPOINTMENT_EDITED);

            $outlookCalendarService->handleEvent($appointment, self::APPOINTMENT_EDITED);

            $appleCalendarService->handleEvent($appointment, self::APPOINTMENT_EDITED);
        }

        if ($appointment->getGoogleCalendarEventId() !== null) {
            $appointmentArray['googleCalendarEventId'] = $appointment->getGoogleCalendarEventId()->getValue();
        }

        if ($appointment->getGoogleMeetUrl() !== null) {
            $appointmentArray['googleMeetUrl'] = $appointment->getGoogleMeetUrl();
        }

        if ($appointment->getOutlookCalendarEventId() !== null) {
            $appointmentArray['outlookCalendarEventId'] = $appointment->getOutlookCalendarEventId()->getValue();
        }

        if ($appointment->getMicrosoftTeamsUrl() !== null) {
            $appointmentArray['microsoftTeamsUrl'] = $appointment->getMicrosoftTeamsUrl();
        }

        if ($appointment->getAppleCalendarEventId() !== null) {
            $appointmentArray['appleCalendarEventId'] = $appointment->getAppleCalendarEventId()->getValue();
        }
    }

    /**
     * @param Event      $event
     * @param Collection $eventPeriods
     * @param array      $eventArray
     * @param string     $command
     * @param array      $skippedIntegrations
     * @param array      $users
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws GraphException
     */
    public function handleEvent($event, $eventPeriods, &$eventArray, $command, $skippedIntegrations = [], $users = [])
    {
        /** @var AbstractGoogleCalendarService $googleCalendarService */
        $googleCalendarService = $this->container->get('infrastructure.google.calendar.service');

        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');

        /** @var AbstractAppleCalendarService $appleCalendarService */
        $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

        /** @var AbstractZoomApplicationService $zoomService */
        $zoomService = $this->container->get('application.zoom.service');

        /** @var AbstractLessonSpaceService $lessonSpaceService */
        $lessonSpaceService = $this->container->get('infrastructure.lesson.space.service');

        if (empty($skippedIntegrations[self::SKIP_LESSON_SPACE])) {
            $lessonSpaceService->handle($event, Entities::EVENT, $eventPeriods);
        }

        if (empty($skippedIntegrations[self::SKIP_ZOOM_MEETING])) {
            $zoomService->handleEventMeeting(
                $event,
                $eventPeriods,
                $command,
                !empty($users['zoomUserId']) ? $users['zoomUserId'] : null
            );
        }

        if (empty($skippedIntegrations[self::SKIP_GOOGLE_CALENDAR])) {
            $googleCalendarService->handleEventPeriodsChange(
                $event,
                $command,
                $eventPeriods,
                !empty($users['providersNew']) ? $users['providersNew'] : null,
                !empty($users['providersRemove']) ? $users['providersRemove'] : null
            );
        }

        if (empty($skippedIntegrations[self::SKIP_OUTLOOK_CALENDAR])) {
            $outlookCalendarService->handleEventPeriod(
                $event,
                $command,
                $eventPeriods,
                !empty($users['providersNew']) ? $users['providersNew'] : null,
                !empty($users['providersRemove']) ? $users['providersRemove'] : null
            );
        }

        if (empty($skippedIntegrations[self::SKIP_APPLE_CALENDAR])) {
            $appleCalendarService->handleEventPeriod(
                $event,
                $command,
                $eventPeriods,
                !empty($users['providersNew']) ? $users['providersNew'] : null,
                !empty($users['providersRemove']) ? $users['providersRemove'] : null
            );
        }

        $eventArray['periods'] = $event->getPeriods()->toArray();
    }
}
