<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Zoom;

use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventPeriodsRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingApprovedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingRejectedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventStatusUpdatedEventHandler;
use AmeliaBooking\Domain\Factory\Zoom\ZoomFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentDeletedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentStatusUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentTimeUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingCanceledEventHandler;
use AmeliaBooking\Infrastructure\Services\Zoom\AbstractZoomService;
use DateTimeZone;

/**
 * Class ZoomApplicationService
 *
 * @package AmeliaBooking\Application\Services\Zoom
 */
class ZoomApplicationService extends AbstractZoomApplicationService
{
    public const SCHEDULED_MEETING = 2;

    public const RECURRING_WITH_FIXED_TIME_MEETING = 8;

    /**
     * @param Appointment $reservation
     * @param string $commandSlug
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    public function handleAppointmentMeeting($reservation, $commandSlug)
    {
        try {
            $this->handleAppointmentMeetingAction($reservation, $commandSlug);
        } catch (\Exception $e) {
            /** @var AppointmentRepository $appointmentRepository */
            $appointmentRepository = $this->container->get("domain.booking.appointment.repository");

            $appointmentRepository->updateErrorColumn($reservation->getId()->getValue(), $e->getMessage());
        }
    }


    /**
     * @param Event      $reservation
     * @param Collection $periods
     * @param string     $commandSlug
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    public function handleEventMeeting($reservation, $periods, $commandSlug, $newZoomUser = null)
    {
        try {
            $this->handleEventMeetingAction($reservation, $periods, $commandSlug, $newZoomUser);
        } catch (\Exception $e) {
            /** @var EventRepository $eventRepository */
            $eventRepository = $this->container->get("domain.booking.event.repository");

            $eventRepository->updateErrorColumn($reservation->getId()->getValue(), $e->getMessage());
        }
    }


    /**
     * @param Appointment $reservation
     * @param string $commandSlug
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    private function handleAppointmentMeetingAction($reservation, $commandSlug)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get("domain.booking.appointment.repository");

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $zoomEnabled = $settingsService
            ->getEntitySettings($reservation->getService()->getSettings())
            ->getZoomSettings()
            ->getEnabled();

        $zoomSettings = $settingsService->getCategorySettings('zoom');

        $zoomMeetingAllowed = $reservation->getStatus()->getValue() === BookingStatus::APPROVED ||
            (
                $reservation->getStatus()->getValue() === BookingStatus::PENDING &&
                $zoomSettings['pendingAppointmentsMeetings']
            );

        if (
            $zoomSettings['accountId'] && $zoomSettings['clientId'] && $zoomSettings['clientSecret'] &&
            $reservation->getProvider()->getZoomUserId() &&
            $zoomEnabled
        ) {
            switch ($commandSlug) {
                case AppointmentDeletedEventHandler::APPOINTMENT_DELETED:
                    if ($reservation->getZoomMeeting()) {
                        $this->removeMeeting($reservation, $appointmentRepository);
                    }
                    break;

                case AppointmentEditedEventHandler::APPOINTMENT_EDITED:
                case BookingAddedEventHandler::BOOKING_ADDED:
                    if (!$reservation->getZoomMeeting() && $zoomMeetingAllowed) {
                        $this->createOrEditAppointmentMeeting($reservation);
                    }
                    break;

                case AppointmentAddedEventHandler::APPOINTMENT_ADDED:
                case AppointmentTimeUpdatedEventHandler::TIME_UPDATED:
                case AppointmentEditedEventHandler::TIME_UPDATED:
                    if ($zoomMeetingAllowed) {
                        $this->createOrEditAppointmentMeeting($reservation);
                    }
                    break;

                case AppointmentEditedEventHandler::ZOOM_USER_CHANGED:
                    if ($reservation->getZoomMeeting()) {
                        $this->removeMeeting($reservation, $appointmentRepository);
                    }
                    if ($zoomMeetingAllowed) {
                        $this->createOrEditAppointmentMeeting($reservation);
                    }
                    break;
                case AppointmentEditedEventHandler::ZOOM_LICENCED_USER_CHANGED:
                    if ($zoomMeetingAllowed) {
                        $this->createOrEditAppointmentMeeting($reservation, true);
                    }
                    break;

                case AppointmentEditedEventHandler::APPOINTMENT_STATUS_AND_ZOOM_LICENCED_USER_CHANGED:
                    $this->processMeetingForStatusChange($reservation, true, true);
                    break;
                case AppointmentStatusUpdatedEventHandler::APPOINTMENT_STATUS_UPDATED:
                case AppointmentEditedEventHandler::BOOKING_STATUS_UPDATED:
                case BookingCanceledEventHandler::BOOKING_CANCELED:
                case BookingApprovedEventHandler::BOOKING_APPROVED:
                case BookingRejectedEventHandler::BOOKING_REJECTED:
                    $this->processMeetingForStatusChange($reservation, false);
                    break;

                case AppointmentEditedEventHandler::APPOINTMENT_STATUS_AND_TIME_UPDATED:
                    $this->processMeetingForStatusChange($reservation, true);
                    break;
            }

            if (
                $reservation->getZoomMeeting() &&
                $reservation->getZoomMeeting()->getId() &&
                (!$reservation->getZoomMeeting()->getStartUrl() || !$reservation->getZoomMeeting()->getStartUrl())
            ) {
                $this->getMeeting($reservation);

                $appointmentRepository->updateFieldById(
                    $reservation->getId()->getValue(),
                    json_encode($reservation->getZoomMeeting()->toArray()),
                    'zoomMeeting'
                );
            }
        }
    }

    /**
     * @param Event      $reservation
     * @param Collection $periods
     * @param string     $commandSlug
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    private function handleEventMeetingAction($reservation, $periods, $commandSlug, $newZoomUser = null)
    {

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $zoomSettings = $settingsService->getCategorySettings('zoom');

        /** @var EventPeriodsRepository $eventPeriodsRepository */
        $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');

        if ($reservation->getZoomUserId() && $zoomSettings['accountId'] && $zoomSettings['clientId'] && $zoomSettings['clientSecret']) {
            switch ($commandSlug) {
                case EventEditedEventHandler::EVENT_ADDED:
                case EventAddedEventHandler::EVENT_ADDED:
                case EventEditedEventHandler::TIME_UPDATED:
                case EventEditedEventHandler::EVENT_PERIOD_ADDED:
                    $this->createOrEditEventMeeting($reservation, $periods);

                    break;

                case EventStatusUpdatedEventHandler::EVENT_STATUS_UPDATED:
                    if ($reservation->getStatus()->getValue() === BookingStatus::APPROVED) {
                        $this->createOrEditEventMeeting($reservation, $periods);
                    } elseif (
                        $reservation->getStatus()->getValue() === BookingStatus::CANCELED ||
                        $reservation->getStatus()->getValue() === BookingStatus::REJECTED
                    ) {
                        /** @var EventPeriod $period */
                        foreach ($periods->getItems() as $period) {
                            $this->removeMeeting($period, $eventPeriodsRepository);
                        }
                    }

                    break;

                case EventEditedEventHandler::ZOOM_USER_CHANGED:
                    if ($reservation->getZoomUserId()->getValue()) {
                        /** @var EventPeriod $period */
                        foreach ($periods->getItems() as $period) {
                            $this->removeMeeting($period, $eventPeriodsRepository);
                        }
                    }
                    $reservation->setZoomUserId(new Name($newZoomUser));
                    $this->createOrEditEventMeeting($reservation, $periods);

                    break;
                case EventEditedEventHandler::ZOOM_LICENCED_USER_CHANGED:
                    $reservation->setZoomUserId(new Name($newZoomUser));
                    $this->createOrEditEventMeeting($reservation, $periods, true);

                    break;
                case EventEditedEventHandler::EVENT_DELETED:
                case EventEditedEventHandler::EVENT_PERIOD_DELETED:
                    /** @var EventPeriod $period */
                    foreach ($periods->getItems() as $period) {
                        $this->removeMeeting($period, $eventPeriodsRepository);
                    }
                    break;
            }

            /** @var EventPeriod $period */
            foreach ($periods->getItems() as $period) {
                if (
                    $period->getZoomMeeting() &&
                    $period->getZoomMeeting()->getId() &&
                    (!$period->getZoomMeeting()->getStartUrl() || !$period->getZoomMeeting()->getStartUrl())
                ) {
                    /** @var EventPeriodsRepository $eventPeriodsRepository */
                    $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');

                    $this->getMeeting($period);

                    $eventPeriodsRepository->updateFieldById(
                        $period->getId()->getValue(),
                        json_encode($period->getZoomMeeting()->toArray()),
                        'zoomMeeting'
                    );
                }
            }
        }
    }

    /**
     * @param Appointment $reservation
     * @param bool $timeUpdated
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    private function processMeetingForStatusChange($reservation, $timeUpdated, $zoomLicencedUsers = false)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get("domain.booking.appointment.repository");

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $zoomSettings = $settingsService->getCategorySettings('zoom');

        switch ($reservation->getStatus()->getValue()) {
            case BookingStatus::REJECTED:
            case BookingStatus::CANCELED:
                if ($reservation->getZoomMeeting()) {
                    $this->removeMeeting($reservation, $appointmentRepository);
                }
                break;

            case BookingStatus::APPROVED:
                if (!$reservation->getZoomMeeting() || $timeUpdated) {
                    $this->createOrEditAppointmentMeeting($reservation, $zoomLicencedUsers);
                }
                break;

            case BookingStatus::PENDING:
                if ($zoomSettings['pendingAppointmentsMeetings'] && (!$reservation->getZoomMeeting() || $timeUpdated)) {
                    $this->createOrEditAppointmentMeeting($reservation, $zoomLicencedUsers);
                } elseif (!$zoomSettings['pendingAppointmentsMeetings'] && $reservation->getZoomMeeting()) {
                    $this->removeMeeting($reservation, $appointmentRepository);
                }
                break;
        }
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        /** @var AbstractZoomService $zoomService */
        $zoomService = $this->container->get('infrastructure.zoom.service');

        return $zoomService->getUsers();
    }

    /**
     * @param Appointment|EventPeriod $reservation
     * @param AbstractRepository      $repository
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     */
    public function removeMeeting($reservation, $repository)
    {
        /** @var AbstractZoomService $zoomService */
        $zoomService = $this->container->get('infrastructure.zoom.service');

        if ($reservation->getZoomMeeting() && $reservation->getZoomMeeting()->getId()) {
            $zoomService->deleteMeeting(
                $reservation->getZoomMeeting()->getId()->getValue()
            );

            $reservation->setZoomMeeting(ZoomFactory::create([]));

            $repository->updateFieldById(
                $reservation->getId()->getValue(),
                null,
                'zoomMeeting'
            );
        }
    }

    /**
     * @param Appointment|EventPeriod $reservation
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    private function getMeeting($reservation)
    {
        /** @var AbstractZoomService $zoomService */
        $zoomService = $this->container->get('infrastructure.zoom.service');

        $zoomResult = $zoomService->getMeeting($reservation->getZoomMeeting()->getId()->getValue());

        if (isset($zoomResult['id'], $zoomResult['join_url'], $zoomResult['start_url'])) {
            $reservation->setZoomMeeting(
                ZoomFactory::create(
                    [
                        'id'       => $zoomResult['id'],
                        'joinUrl'  => $zoomResult['join_url'],
                        'startUrl' => $zoomResult['start_url'],
                    ]
                )
            );
        }
    }

    /**
     * @param Appointment $reservation
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    private function createOrEditAppointmentMeeting($reservation, $zoomLicencedUsers = false)
    {
        /** @var AbstractZoomService $zoomService */
        $zoomService = $this->container->get('infrastructure.zoom.service');

        $meetingStart = DateTimeService::getCustomDateTimeObject(
            $reservation->getBookingStart()->getValue()->format('Y-m-d H:i:s')
        );

        $meetingEnd = DateTimeService::getCustomDateTimeObject(
            $reservation->getBookingEnd()->getValue()->format('Y-m-d H:i:s')
        );

        $meetingData = $this->getMeetingData(
            $reservation,
            self::SCHEDULED_MEETING,
            $meetingStart,
            $meetingEnd,
            ($meetingEnd->setTimezone(new DateTimeZone('UTC'))->getTimestamp() -
                $meetingStart->setTimezone(new DateTimeZone('UTC'))->getTimestamp()
            ) / 60
        );

        if ($reservation->getZoomMeeting() && $reservation->getZoomMeeting()->getId()) {
            if ($zoomLicencedUsers) {
                $meetingData['settings'] = ['alternative_hosts' => $reservation->getProvider()->getZoomUserId()->getValue()];
            }
            $zoomService->updateMeeting(
                $reservation->getZoomMeeting()->getId()->getValue(),
                $meetingData
            );
        } else {
            $zoomResult = $zoomService->createMeeting(
                $reservation->getProvider()->getZoomUserId()->getValue(),
                $meetingData
            );

            if (isset($zoomResult['id'], $zoomResult['join_url'], $zoomResult['start_url'])) {
                /** @var AppointmentRepository $appointmentRepository */
                $appointmentRepository = $this->container->get("domain.booking.appointment.repository");

                $reservation->setZoomMeeting(
                    ZoomFactory::create(
                        [
                            'id'       => $zoomResult['id'],
                            'joinUrl'  => $zoomResult['join_url'],
                            'startUrl' => $zoomResult['start_url'],
                        ]
                    )
                );

                $appointmentRepository->updateFieldById(
                    $reservation->getId()->getValue(),
                    json_encode($reservation->getZoomMeeting()->toArray()),
                    'zoomMeeting'
                );
            }
        }
    }

    /**
     * @param Event      $reservation
     * @param Collection $periods
     *
     * @return void
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    private function createOrEditEventMeeting($reservation, $periods, $zoomLicencedUsers = false)
    {
        /** @var AbstractZoomService $zoomService */
        $zoomService = $this->container->get('infrastructure.zoom.service');

        /** @var EventPeriodsRepository $eventPeriodsRepository */
        $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');

        $eventPeriodsRepository->beginTransaction();

        /** @var EventPeriod $period */
        foreach ($periods->getItems() as $period) {
            $periodStartDateString = $period->getPeriodStart()->getValue()->format('Y-m-d');

            $periodEndDateString = $period->getPeriodEnd()->getValue()->format('Y-m-d');

            $periodStartTimeString = $period->getPeriodStart()->getValue()->format('H:i:s');

            $periodEndTimeString = $period->getPeriodEnd()->getValue()->format('H:i:s');

            $meetingStart = DateTimeService::getCustomDateTimeObject(
                $periodStartDateString . ' ' . $periodStartTimeString
            );

            $meetingEnd = DateTimeService::getCustomDateTimeObject(
                $periodStartDateString . ' ' . $periodEndTimeString
            );

            $meetingData = $this->getMeetingData(
                $reservation,
                $periodStartDateString === $periodEndDateString ?
                    self::SCHEDULED_MEETING : self::RECURRING_WITH_FIXED_TIME_MEETING,
                $meetingStart,
                DateTimeService::getCustomDateTimeObject(
                    $periodEndDateString . ' ' . $periodEndTimeString
                ),
                ($meetingEnd->setTimezone(new DateTimeZone('UTC'))->getTimestamp() -
                    $meetingStart->setTimezone(new DateTimeZone('UTC'))->getTimestamp()
                ) / 60
            );

            if ($period->getZoomMeeting() && $period->getZoomMeeting()->getId()) {
                if ($zoomLicencedUsers) {
                    $meetingData['settings'] = ['alternative_hosts' => $reservation->getZoomUserId()->getValue()];
                }
                $zoomService->updateMeeting(
                    $period->getZoomMeeting()->getId()->getValue(),
                    $meetingData
                );
            } else {
                $zoomResult = $zoomService->createMeeting(
                    $reservation->getZoomUserId()->getValue(),
                    $meetingData
                );

                if (isset($zoomResult['id'], $zoomResult['join_url'], $zoomResult['start_url'])) {
                    $period->setZoomMeeting(
                        ZoomFactory::create(
                            [
                                'id'       => $zoomResult['id'],
                                'joinUrl'  => $zoomResult['join_url'],
                                'startUrl' => $zoomResult['start_url'],
                            ]
                        )
                    );

                    $eventPeriodsRepository->updateFieldById(
                        $period->getId()->getValue(),
                        json_encode($period->getZoomMeeting()->toArray()),
                        'zoomMeeting'
                    );
                }
            }
        }

        $eventPeriodsRepository->commit();
    }

    /**
     * @param Appointment|Event $reservation
     * @param int               $type
     * @param \DateTime         $meetingStart
     * @param \DateTime         $meetingEnd
     * @param int               $duration
     *
     * @return array
     *
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    private function getMeetingData($reservation, $type, $meetingStart, $meetingEnd, $duration)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get('application.placeholder.' . $reservation->getType()->getValue() .  '.service');

        $zoomSettings = $settingsService->getCategorySettings('zoom');

        $placeholderData = $placeholderService->getPlaceholdersData($reservation->toArray());

        $agenda = $placeholderService->applyPlaceholders(
            $zoomSettings['meetingAgenda'],
            $placeholderData
        );

        $meetingData = [
            'topic'      => $placeholderService->applyPlaceholders(
                $zoomSettings['meetingTitle'],
                $placeholderData
            ),
            'agenda'      => strlen($agenda) > 2000 ? substr($agenda, 0, 2000) : $agenda,
            'type'       => $type,
            'start_time' =>
                $meetingStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'duration'   => $duration
        ];

        if ($type === self::RECURRING_WITH_FIXED_TIME_MEETING) {
            $meetingData['recurrence'] = [
                'type'          => 1,
                'end_date_time' => $meetingEnd->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        return $meetingData;
    }
}
