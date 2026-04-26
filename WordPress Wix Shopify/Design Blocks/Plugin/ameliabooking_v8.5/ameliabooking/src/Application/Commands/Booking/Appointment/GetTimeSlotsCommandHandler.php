<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService as ApplicationTimeSlotService;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\Entity\EntityService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\SlotsEntities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Services\Apple\AbstractAppleCalendarService;
use AmeliaBooking\Infrastructure\Services\Google\AbstractGoogleCalendarService;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use DateTimeZone;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetTimeSlotsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetTimeSlotsCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'serviceId'
    ];

    /**
     * @param GetTimeSlotsCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function handle(GetTimeSlotsCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var EntityService $entityService */
        $entityService = $this->container->get('domain.entity.service');

        /** @var ApplicationTimeSlotService $applicationTimeSlotService */
        $applicationTimeSlotService = $this->container->get('application.timeSlot.service');

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $props = [
            'serviceId'            => $command->getField('serviceId'),
            'providerIds'          => $command->getField('providerIds'),
            'locationId'           => $command->getField('locationId'),
            'locationIds'          => $command->getField('locationIds'),
            'extras'               => $command->getField('extras'),
            'excludeAppointmentId' => $command->getField('excludeAppointmentId'),
            'personsCount'         => $command->getField('group') ? $command->getField('persons') : null,
            'isFrontEndBooking'    => $command->getField('page') === 'booking' || $command->getField('page') === 'cabinet',
            'totalPersons'         => $command->getField('persons'),
            'serviceDuration'      => $command->getField('serviceDuration'),
            'monthsLoad'           => $command->getField('monthsLoad'),
            'startDateTime'        => $command->getField('startDateTime'),
            'endDateTime'          => $command->getField('endDateTime'),
            'queryTimeZone'        => $command->getField('queryTimeZone'),
            'timeZone'             => $command->getField('timeZone'),
            'structured'           => $command->getField('structured'),

        ];

        $props = apply_filters('amelia_before_get_timeslots_filter', $props);

        do_action('amelia_before_get_timeslots', $props);

        $isFrontEndBooking = $props['isFrontEndBooking'];

        /** @var SlotsEntities $slotsEntities */
        $slotsEntities = $applicationTimeSlotService->getSlotsEntities(
            [
                'isFrontEndBooking' => $isFrontEndBooking,
                'providerIds'       => $props['providerIds'],
            ]
        );

        $settings = $applicationTimeSlotService->getSlotsSettings($isFrontEndBooking, $slotsEntities, $props);

        $lastBookedProviderId = null;

        /** @var SlotsEntities $filteredSlotEntities */
        $filteredSlotEntities = $entityService->getFilteredSlotsEntities(
            $settings,
            $props,
            $slotsEntities
        );

        /** @var Service $service */
        $service = $filteredSlotEntities->getServices()->getItem($props['serviceId']);

        if ($props['serviceDuration']) {
            $service->setDuration(new PositiveDuration($props['serviceDuration']));
        }

        $minimumBookingTimeInSeconds = $settingsDS
            ->getEntitySettings($service->getSettings())
            ->getGeneralSettings()
            ->getMinimumTimeRequirementPriorToBooking();

        $maximumBookingTimeInDays = $settingsDS
            ->getEntitySettings($service->getSettings())
            ->getGeneralSettings()
            ->getNumberOfDaysAvailableForBooking();

        $monthsLoad = $props['monthsLoad'];

        $loadGeneratedPeriod = $monthsLoad &&
            !$props['endDateTime'];

        $timeZone = $props['queryTimeZone'] ?: DateTimeService::getTimeZone()->getName();

        $queryStartDateTime = $props['startDateTime'] ?
            DateTimeService::getDateTimeObjectInTimeZone(
                $props['startDateTime'],
                $timeZone
            )->setTimezone(DateTimeService::getTimeZone()) : null;

        $queryEndDateTime = $props['endDateTime'] ?
            DateTimeService::getDateTimeObjectInTimeZone(
                $props['endDateTime'],
                $timeZone
            )->setTimezone(DateTimeService::getTimeZone()) : null;

        $minimumDateTime = $applicationTimeSlotService->getMinimumDateTimeForBooking(
            null,
            $isFrontEndBooking,
            $minimumBookingTimeInSeconds
        );

        $startDateTime = $queryStartDateTime ?:
            $applicationTimeSlotService->getMinimumDateTimeForBooking(
                null,
                $isFrontEndBooking,
                $minimumBookingTimeInSeconds
            );

        $endDateTime = $queryEndDateTime ?:
            $applicationTimeSlotService->getMaximumDateTimeForBooking(
                null,
                $isFrontEndBooking,
                $maximumBookingTimeInDays
            );

        $maximumDateTime = $applicationTimeSlotService->getMaximumDateTimeForBooking(
            null,
            $isFrontEndBooking,
            $maximumBookingTimeInDays
        );

        if ($isFrontEndBooking) {
            $startDateTime = $startDateTime < $minimumDateTime ? $minimumDateTime : $startDateTime;

            $endDateTime = $endDateTime > $maximumDateTime ? $maximumDateTime : $endDateTime;
        }

        // set initial search period if query dates are not set
        if ($loadGeneratedPeriod) {
            $endDateTime = DateTimeService::getCustomDateTimeObject(
                $startDateTime->format('Y-m-d H:i:s')
            )->setTimezone(
                new DateTimeZone($timeZone)
            );

            $endDateTime->modify('first day of this month');

            $endDateTime->modify('+' . ($monthsLoad - 1) .  'months');

            $endDateTime->modify('last day of this month');

            $endDateTime->modify('+12days');

            $endDateTime->setTime(23, 59, 59);

            if ($isFrontEndBooking) {
                $endDateTime = $endDateTime > $maximumDateTime ?
                    DateTimeService::getDateTimeObjectInTimeZone(
                        $maximumDateTime->format('Y-m-d H:i'),
                        $timeZone
                    ) : $endDateTime;
            }

            $endDateTime->setTimezone(DateTimeService::getTimeZone());
        }

        /** @var Service $filteredSlotEntitiesService */
        foreach ($filteredSlotEntities->getServices()->getItems() as $filteredSlotEntitiesService) {
            if ($filteredSlotEntitiesService->getId()->getValue() === $service->getId()->getValue()) {
                $filteredSlotEntitiesService->setDuration($service->getDuration());

                break;
            }
        }

        /** @var Provider $filteredSlotEntitiesProvider */
        foreach ($filteredSlotEntities->getProviders()->getItems() as $filteredSlotEntitiesProvider) {
            /** @var Service $providerService */
            foreach ($filteredSlotEntitiesProvider->getServiceList()->getItems() as $providerService) {
                if ($providerService->getId()->getValue() === $service->getId()->getValue()) {
                    $providerService->setDuration($service->getDuration());

                    break;
                }
            }
        }

        $freeSlots = $applicationTimeSlotService->getSlotsByProps(
            $settings,
            array_merge(
                $props,
                [
                    'startDateTime' => $startDateTime,
                    'endDateTime'   => $endDateTime,
                ]
            ),
            $filteredSlotEntities
        );

        if ($loadGeneratedPeriod) {
            // search with new period until slots are not found
            while (!$freeSlots['available'] && $endDateTime && $endDateTime <= $maximumDateTime) {
                $startDateTime = DateTimeService::getCustomDateTimeObject(
                    $endDateTime->format('Y-m-d H:i:s')
                )->setTimezone(
                    new DateTimeZone($timeZone)
                );

                $startDateTime->setTime(0, 0, 0);

                $endDateTime->modify('first day of this month');

                $endDateTime->modify('+' . ($monthsLoad - 1) .  'months');

                $endDateTime->modify('last day of this month');

                $endDateTime->modify('+12days');

                $endDateTime->setTime(23, 59, 59);

                if ($isFrontEndBooking) {
                    $endDateTime = $endDateTime > $maximumDateTime ?
                        DateTimeService::getDateTimeObjectInTimeZone(
                            $maximumDateTime->format('Y-m-d H:i'),
                            $timeZone
                        ) : $endDateTime;
                }

                $endDateTime->setTimezone(DateTimeService::getTimeZone());

                AbstractGoogleCalendarService::$providersGoogleEvents = [];

                AbstractOutlookCalendarService::$providersOutlookEvents = [];

                AbstractAppleCalendarService::$providersAppleEvents = [];

                $freeSlots = $applicationTimeSlotService->getSlotsByProps(
                    $settings,
                    array_merge(
                        $props,
                        [
                            'startDateTime' => $startDateTime,
                            'endDateTime'   => $endDateTime,
                        ]
                    ),
                    $filteredSlotEntities
                );

                $endDateTimeCopy = clone $endDateTime;

                $maximumDateTimeCopy = clone $maximumDateTime;

                if (
                    $endDateTimeCopy->format('Y-m-d H:i') === $maximumDateTimeCopy->format('Y-m-d H:i') ||
                    ($endDateTimeCopy->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i') ===
                        $maximumDateTimeCopy->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i')) ||
                    ($endDateTimeCopy > $maximumDateTimeCopy)
                ) {
                    break;
                }
            }

            // search once more if first available date is in 11 days added to endDateTime (days outside calendar on frontend form)
            foreach (array_slice($freeSlots['available'], 0, 1, true) as $slotDate => $slotTimes) {
                if (substr($slotDate, 0, 7) === $endDateTime->format('Y-m')) {
                    $endDateTime->modify('last day of this month');

                    $endDateTime->modify('+12days');

                    $endDateTime->setTime(23, 59, 59);

                    if ($isFrontEndBooking) {
                        $endDateTime = $endDateTime > $maximumDateTime ?
                            DateTimeService::getDateTimeObjectInTimeZone(
                                $maximumDateTime->format('Y-m-d H:i'),
                                $timeZone
                            ) : $endDateTime;
                    }

                    AbstractGoogleCalendarService::$providersGoogleEvents = [];

                    AbstractOutlookCalendarService::$providersOutlookEvents = [];

                    AbstractAppleCalendarService::$providersAppleEvents = [];

                    $freeSlots = $applicationTimeSlotService->getSlotsByProps(
                        $settings,
                        array_merge(
                            $props,
                            [
                                'startDateTime' => $startDateTime,
                                'endDateTime'   => $endDateTime,
                            ]
                        ),
                        $filteredSlotEntities
                    );
                }
            }
        }

        $busyness = [];

        foreach ($freeSlots['available'] as $slotDate => $slotTimes) {
            $busyness[$slotDate] = round(
                count(!empty($freeSlots['occupied'][$slotDate]) ? $freeSlots['occupied'][$slotDate] : []) /
                (count(!empty($freeSlots['available'][$slotDate]) ? $freeSlots['available'][$slotDate] : []) +
                    count(!empty($freeSlots['occupied'][$slotDate]) ? $freeSlots['occupied'][$slotDate] : []))
                * 100
            );
        }

        $converted = ['available' => [], 'occupied' => []];

        $isUtcResponse = ($settingsDS->getSetting('general', 'showClientTimeZone') && $isFrontEndBooking) ||
            $props['timeZone'];

        if ($isUtcResponse) {
            foreach (['available', 'occupied'] as $type) {
                foreach ($freeSlots[$type] as $slotDate => $slotTimes) {
                    foreach ($freeSlots[$type][$slotDate] as $slotTime => $slotTimesProviders) {
                        $convertedSlotParts = explode(
                            ' ',
                            $props['timeZone'] ?
                                DateTimeService::getCustomDateTimeObjectInTimeZone(
                                    $slotDate . ' ' . $slotTime,
                                    $props['timeZone']
                                )->format('Y-m-d H:i') :
                                DateTimeService::getCustomDateTimeObjectInUtc(
                                    $slotDate . ' ' . $slotTime
                                )->format('Y-m-d H:i')
                        );

                        $converted[$type][$convertedSlotParts[0]][$convertedSlotParts[1]] = $slotTimesProviders;
                    }
                }
            }
        }

        if (count($props['providerIds']) !== 1 && $settingsDS->getSetting('appointments', 'employeeSelection') === 'roundRobin') {
            /** @var AppointmentRepository $appointmentRepository */
            $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

            $lastBookedProviderId = $appointmentRepository->getLastBookedEmployee($props['providerIds']);
        }

        $resultData = [
          'slots' =>  $converted['available'] ?: $freeSlots['available'],
          'occupied' => $converted['occupied'] ?: $freeSlots['occupied'],
          'minimum' => $isUtcResponse ?
            $minimumDateTime->setTimezone(
                new DateTimeZone('UTC')
            )->format('Y-m-d H:i') : $minimumDateTime->format('Y-m-d H:i'),
          'maximum' => $isUtcResponse ?
            $maximumDateTime->setTimezone(
                new DateTimeZone('UTC')
            )->format('Y-m-d H:i') : $maximumDateTime->format('Y-m-d H:i'),
          'busyness' => $busyness,
          'lastBookedProviderId' => $lastBookedProviderId,
          'appCount' => $freeSlots['appCount'],
          'duration' => $freeSlots['duration'],
        ];


        $resultData = apply_filters('amelia_get_timeslots_filter', $resultData, $props);

        do_action('amelia_get_timeslots', $resultData, $props);


        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved free slots');
        $result->setData(
            [
                'minimum' => $resultData['minimum'],
                'maximum'   => $resultData['maximum'],
                'slots'     => $resultData['slots'],
                'occupied'  => $resultData['occupied'],
                'busyness'  => $resultData['busyness'],
                'lastProvider' => $resultData['lastBookedProviderId'],
                'appCount' => $resultData['appCount'],
                'duration' => $resultData['duration'],
            ]
        );

        return $result;
    }
}
