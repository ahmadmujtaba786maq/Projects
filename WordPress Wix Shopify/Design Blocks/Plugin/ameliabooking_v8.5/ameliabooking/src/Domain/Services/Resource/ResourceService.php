<?php

namespace AmeliaBooking\Domain\Services\Resource;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Bookable\Service\ResourceFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Interval\IntervalService;
use AmeliaBooking\Domain\Services\Schedule\ScheduleService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\Entity\Bookable\Service\Resource;
use DateTimeZone;
use Exception;

/**
 * Class ResourceService
 *
 * @package AmeliaBooking\Domain\Services\Resource
 */
class ResourceService extends AbstractResourceService
{
    /**
     * ResourceService constructor.
     *
     * @param IntervalService $intervalService
     * @param ScheduleService $scheduleService
     */
    public function __construct(
        $intervalService,
        $scheduleService
    ) {
        $this->intervalService = $intervalService;

        $this->scheduleService = $scheduleService;
    }

    /**
     * set substitute resources instead of resources that are not shred between services/locations
     *
     * @param Collection $resources
     * @param array      $entitiesIds
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function setNonSharedResources($resources, $entitiesIds)
    {
        /** @var Resource $resource */
        foreach ($resources->getItems() as $resourceIndex => $resource) {
            if ($resource->getShared() && $resource->getShared()->getValue()) {
                /** @var Collection $substituteResources */
                $substituteResources = new Collection();

                $resourceEntitiesIds = [];

                $substituteResourceEntities = [];

                foreach ($resource->getEntities() as $index => $item) {
                    if ($item['entityType'] === $resource->getShared()->getValue()) {
                        $resourceEntitiesIds[] = $item['entityId'];
                    } else {
                        $substituteResourceEntities[] = $item;
                    }
                }

                foreach ($resourceEntitiesIds ?: $entitiesIds[$resource->getShared()->getValue()] as $entityId) {
                    /** @var Resource $substituteResource */
                    $substituteResource = ResourceFactory::create($resource->toArray());

                    $substituteResource->setEntities(
                        array_merge(
                            $substituteResourceEntities,
                            [
                                [
                                    'id'         => null,
                                    'resourceId' => $resource->getId()->getValue(),
                                    'entityType' => $resource->getShared()->getValue(),
                                    'entityId'   => $entityId,
                                ]
                            ]
                        )
                    );

                    $substituteResources->addItem($substituteResource);
                }

                $resources->deleteItem($resourceIndex);

                /** @var Resource $substituteResource */
                foreach ($substituteResources->getItems() as $substituteResource) {
                    $resources->addItem($substituteResource);
                }
            }
        }
    }

    /**
     * get collection of resources for service
     *
     * @param Collection $resources
     * @param int        $serviceId
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function getServiceResources($resources, $serviceId)
    {
        /** @var Collection $serviceResources */
        $serviceResources = new Collection();

        /** @var Resource $resource */
        foreach ($resources->getItems() as $resource) {
            $resourceEntities = $resource->getEntities();

            $hasService = false;

            $hasAnyService = false;

            foreach ($resourceEntities as $item) {
                if ($item['entityType'] === 'service') {
                    $hasAnyService = true;

                    if ($item['entityId'] === $serviceId) {
                        $hasService = true;

                        break;
                    }
                }
            }

            if (!$hasAnyService || $hasService) {
                $serviceResources->addItem($resource);
            }
        }

        return $serviceResources;
    }

    /**
     * get collection of resources that are not connected to any location
     *
     * @param Collection $resources
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function getProvidersResources($resources)
    {
        /** @var Collection $providersResources */
        $providersResources = new Collection();

        /** @var Resource $resource */
        foreach ($resources->getItems() as $resource) {
            $hasSelectedLocations = false;

            foreach ($resource->getEntities() as $item) {
                if ($item['entityType'] === 'location') {
                    $hasSelectedLocations = true;

                    break;
                }
            }

            if (!$hasSelectedLocations) {
                $providersResources->addItem($resource);
            }
        }

        return $providersResources;
    }

    /**
     * get collection of resources that are connected to at least one location
     *
     * @param Collection $resources
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function getLocationsResources($resources)
    {
        /** @var Collection $locationsResources */
        $locationsResources = new Collection();

        /** @var Resource $resource */
        foreach ($resources->getItems() as $resource) {
            $hasSelectedLocations = false;

            foreach ($resource->getEntities() as $item) {
                if ($item['entityType'] === 'location') {
                    $hasSelectedLocations = true;

                    break;
                }
            }

            if ($hasSelectedLocations) {
                $locationsResources->addItem($resource);
            }
        }

        return $locationsResources;
    }

    /**
     * get providers id values for resources
     *
     * @param Collection $resources
     *
     * @return array
     */
    public function getResourcesProvidersIds($resources)
    {
        $resourceProvidersIds = [];

        $resourcesData = [];

        /** @var Resource $resource */
        foreach ($resources->getItems() as $resource) {
            $resourceEntities = $resource->getEntities();

            $resourcesData[$resource->getId()->getValue()] = [
                'service'  => [],
                'employee' => [],
                'location' => [],
            ];

            foreach ($resourceEntities as $item) {
                foreach (['service', 'employee', 'location'] as $type) {
                    if ($item['entityType'] === $type) {
                        $resourcesData[$resource->getId()->getValue()][$type][(int)$item['entityId']] = true;

                        break;
                    }
                }
            }

            if (!$resourcesData[$resource->getId()->getValue()]['employee']) {
                return [];
            }
        }

        foreach ($resourcesData as $resourceData) {
            if ($resourceData['employee']) {
                $resourceProvidersIds = array_merge(array_keys($resourceData['employee']), $resourceProvidersIds);
            }
        }

        return $resourceProvidersIds;
    }

    /**
     * get collection of appointments that are using resources
     *
     * @param Collection $resources
     * @param Collection $appointments
     * @param int|null   $excludeAppointmentId
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function getResourcedAppointments($resources, $appointments, $excludeAppointmentId)
    {
        /** @var Collection $resourcedAppointments */
        $resourcedAppointments = new Collection();

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            $entityIds = [
                'service'  => $appointment->getServiceId()->getValue(),
                'employee' => $appointment->getProviderId()->getValue(),
                'location' => $appointment->getLocationId() ? $appointment->getLocationId()->getValue() : null,
            ];

            /** @var Resource $resource */
            foreach ($resources->getItems() as $resourceIndex => $resource) {
                $hasResource = false;

                $entityInspect = [
                    'service'  => false,
                    'employee' => false,
                    'location' => false,
                ];

                $entityResourced = [
                    'service'  => false,
                    'employee' => false,
                    'location' => false,
                ];

                foreach ($resource->getEntities() as $item) {
                    $entityInspect[$item['entityType']] = true;

                    if ($item['entityId'] === $entityIds[$item['entityType']]) {
                        $entityResourced[$item['entityType']] = true;
                    }
                }

                if (
                    ($entityInspect['service'] ? $entityResourced['service'] : true) &&
                    ($entityInspect['employee'] ? $entityResourced['employee'] : true) &&
                    ($entityInspect['location'] ? $entityResourced['location'] : true)
                ) {
                    $hasResource = true;
                }

                if (
                    $hasResource &&
                    (!$excludeAppointmentId || $appointment->getId()->getValue() !== $excludeAppointmentId)
                ) {
                    /** @var Appointment|null $resourceAppointment */
                    $resourceAppointment = null;

                    if ($resourcedAppointments->keyExists($appointment->getId()->getValue())) {
                        $resourceAppointment = $resourcedAppointments->getItem($appointment->getId()->getValue());
                    } else {
                        $resourceAppointment = AppointmentFactory::create($appointment->toArray());

                        $resourcedAppointments->addItem($resourceAppointment, $appointment->getId()->getValue());
                    }

                    $resourceAppointment->getResources()->addItem($resource, $resourceIndex);
                }
            }
        }

        return $resourcedAppointments;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * get intervals where resources are fully used
     *
     * @param Collection $resources
     * @param Collection $appointments
     * @param Collection $originalAppointments
     * @param int        $personsCount
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function getResourcedIntervals($resources, $appointments, $originalAppointments, $personsCount)
    {
        $resourcesIntervals = [];

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            $count = 0;

            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                $count += $booking->getPersons()->getValue();
            }

            foreach ($appointment->getResources()->keys() as $resourceIndex) {
                /** @var Resource $resource */
                $resource = $resources->getItem($resourceIndex);

                $resourcesIntervals[$resourceIndex][$appointment->getBookingStart()->getValue()->format('Y-m-d')][] = [
                    'start' =>
                        $this->intervalService->getSeconds(
                            $appointment->getBookingStart()->getValue()->format('H:i:s')
                        ),
                    'end'   =>
                        $this->intervalService->getSeconds(
                            $appointment->getBookingEnd()->getValue()->format('H:i:s')
                        ) ?: 86400,
                    'count' => $resource->getCountAdditionalPeople() &&
                        $resource->getCountAdditionalPeople()->getValue() ? $count : 1,
                    'ids'   => [$appointment->getId()->getValue()],
                ];
            }
        }

        $resultIntervals = [];

        foreach ($resourcesIntervals as $resourceIndex => $resourceDates) {
            /** @var Resource $resource */
            $resource = $resources->getItem($resourceIndex);

            $groupResource = $resource->getCountAdditionalPeople() && $resource->getCountAdditionalPeople()->getValue();

            foreach ($resourceDates as $date => $resourceDate) {
                $count = sizeof($resourceDate);

                for ($i = 0; $i < $count; $i++) {
                    $appointmentIntervals = [$resourceDate[$i]];

                    for ($j = 0; $j < $count; $j++) {
                        if ($i !== $j) {
                            $comparableInterval = $resourceDate[$j];

                            $newAppointmentIntervals = [];

                            foreach ($appointmentIntervals as $interval) {
                                if (
                                    $interval['start'] <= $comparableInterval['start'] &&
                                    $interval['end'] > $comparableInterval['start'] &&
                                    $interval['end'] <= $comparableInterval['end']
                                ) {
                                    if ($interval['start'] !== $comparableInterval['start']) {
                                        $newAppointmentIntervals[] = [
                                            'start' => $interval['start'],
                                            'end'   => $comparableInterval['start'],
                                            'count' => $interval['count'],
                                            'ids'   => $interval['ids'],
                                        ];
                                    }

                                    $newAppointmentIntervals[] = [
                                        'start' => $comparableInterval['start'],
                                        'end'   => $interval['end'],
                                        'count' => $interval['count'] + ($groupResource ? $comparableInterval['count'] : 1),
                                        'ids'   => array_merge($interval['ids'], $comparableInterval['ids'])
                                    ];
                                } elseif (
                                    $interval['start'] >= $comparableInterval['start'] &&
                                    $interval['start'] < $comparableInterval['end'] &&
                                    $interval['end'] >= $comparableInterval['end']
                                ) {
                                    $newAppointmentIntervals[] = [
                                        'start' => $interval['start'],
                                        'end'   => $comparableInterval['end'],
                                        'count' => $interval['count'] + ($groupResource ? $comparableInterval['count'] : 1),
                                        'ids'   => array_merge($interval['ids'], $comparableInterval['ids'])
                                    ];

                                    if ($interval['end'] !== $comparableInterval['end']) {
                                        $newAppointmentIntervals[] = [
                                            'start' => $comparableInterval['end'],
                                            'end'   => $interval['end'],
                                            'count' => $interval['count'],
                                            'ids'   => $interval['ids'],
                                        ];
                                    }
                                } elseif (
                                    $interval['start'] <= $comparableInterval['start'] &&
                                    $interval['end'] >= $comparableInterval['end']
                                ) {
                                    if ($interval['start'] !== $comparableInterval['start']) {
                                        $newAppointmentIntervals[] = [
                                            'start' => $interval['start'],
                                            'end'   => $comparableInterval['start'],
                                            'count' => $interval['count'],
                                            'ids'   => $interval['ids'],
                                        ];
                                    }

                                    $newAppointmentIntervals[] = [
                                        'start' => $comparableInterval['start'],
                                        'end'   => $comparableInterval['end'],
                                        'count' => $interval['count'] + ($groupResource ? $comparableInterval['count'] : 1),
                                        'ids'   => array_merge($interval['ids'], $comparableInterval['ids'])
                                    ];

                                    if ($interval['end'] !== $comparableInterval['end']) {
                                        $newAppointmentIntervals[] = [
                                            'start' => $comparableInterval['end'],
                                            'end'   => $interval['end'],
                                            'count' => $interval['count'],
                                            'ids'   => $interval['ids'],
                                        ];
                                    }
                                } elseif (
                                    $interval['start'] >= $comparableInterval['start'] &&
                                    $interval['end'] <= $comparableInterval['end']
                                ) {
                                    $newAppointmentIntervals[] = [
                                        'start' => $interval['start'],
                                        'end'   => $interval['end'],
                                        'count' => $interval['count'] + ($groupResource ? $comparableInterval['count'] : 1),
                                        'ids'   => array_merge($interval['ids'], $comparableInterval['ids'])
                                    ];
                                } else {
                                    $newAppointmentIntervals[] = [
                                        'start' => $interval['start'],
                                        'end'   => $interval['end'],
                                        'count' => $interval['count'],
                                        'ids'   => $interval['ids'],
                                    ];
                                }
                            }

                            $appointmentIntervals = $newAppointmentIntervals
                                ? $newAppointmentIntervals : $appointmentIntervals;
                        }
                    }

                    if ($appointmentIntervals) {
                        $resultIntervals[$resourceIndex][$date][] = $appointmentIntervals;
                    }
                }
            }
        }

        $occupiedIntervals = [];

        $resourcedAppointmentsIds = [];

        foreach ($resultIntervals as $resourceIndex => $resourceDates) {
            /** @var Resource $resource */
            $resource = $resources->getItem($resourceIndex);

            $groupResource = $resource->getCountAdditionalPeople() && $resource->getCountAdditionalPeople()->getValue();

            foreach ($resourceDates as $date => $resourceDate) {
                foreach ($resourceDate as $intervals) {
                    foreach ($intervals as $interval) {
                        if (
                            !isset($occupiedIntervals[$resourceIndex][$date][$interval['start']]) &&
                            (
                                $groupResource
                                ? $resource->getQuantity()->getValue() < $interval['count'] + $personsCount
                                : $resource->getQuantity()->getValue() <= $interval['count']
                            )
                        ) {
                            if ($groupResource) {
                                $resourcedAppointmentsIds = array_merge($resourcedAppointmentsIds, $interval['ids']);
                            }

                            $occupiedIntervals[$resourceIndex][$date][$interval['start']] = $interval['end'];
                        }
                    }
                }

                if (!empty($occupiedIntervals[$resourceIndex][$date])) {
                    $starts = array_keys($occupiedIntervals[$resourceIndex][$date]);

                    sort($starts);

                    $mergedIntervals = [];

                    $lastEnd = null;

                    $joinedStart = null;

                    for ($i = 0; $i < sizeof($starts); $i++) {
                        if ($lastEnd === $starts[$i]) {
                            $mergedIntervals[$joinedStart] = $occupiedIntervals[$resourceIndex][$date][$starts[$i]];
                        } else {
                            $mergedIntervals[$starts[$i]] = $occupiedIntervals[$resourceIndex][$date][$starts[$i]];

                            $joinedStart = $starts[$i];
                        }

                        $lastEnd = $occupiedIntervals[$resourceIndex][$date][$starts[$i]];
                    }

                    $occupiedIntervals[$resourceIndex][$date] = $mergedIntervals;
                }
            }
        }

        foreach (array_unique($resourcedAppointmentsIds) as $id) {
            /** @var Appointment $appointment */
            $appointment = $originalAppointments->getItem($id);

            $appointment->setFull(new BooleanValueObject(true));
        }

        return $occupiedIntervals;
    }

    /**
     * remove available intervals for providers connected to resources
     *
     * @param Collection $providersResources
     * @param Collection $appointments
     * @param Collection $providers
     * @param int|null   $excludeAppointmentId
     * @param int        $personsCount
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function manageProvidersWithResources(
        $providersResources,
        $appointments,
        $providers,
        $excludeAppointmentId,
        $personsCount
    ) {
        /** @var Collection $fakeAppointments */
        $fakeAppointments = new Collection();

        $resourcedIntervals = $this->getResourcedIntervals(
            $providersResources,
            $this->getResourcedAppointments($providersResources, $appointments, $excludeAppointmentId),
            $appointments,
            $personsCount
        );

        foreach ($resourcedIntervals as $dateIntervals) {
            foreach ($dateIntervals as $dateString => $intervals) {
                foreach ($intervals as $start => $end) {
                    $timeStart = sprintf('%02d', floor($start / 3600)) . ':'
                        . sprintf('%02d', floor(($start / 60) % 60));

                    $timeEnd = sprintf('%02d', floor($end / 3600)) . ':'
                        . sprintf('%02d', floor(($end / 60) % 60));

                    $fakeAppointments->addItem(
                        AppointmentFactory::create(
                            [
                                'bookingStart'       => $dateString . ' ' . $timeStart,
                                'bookingEnd'         => $dateString . ' ' . $timeEnd,
                                'notifyParticipants' => false,
                                'serviceId'          => 0,
                                'providerId'         => 0,
                            ]
                        )
                    );
                }
            }
        }

        $providersIds = $providers->keys();

        /** @var Resource $resource */
        foreach ($providersResources->getItems() as $resource) {
            $resourceProvidersIds = [];

            foreach ($resource->getEntities() as $item) {
                if ($item['entityType'] === 'employee') {
                    $resourceProvidersIds[$item['entityId']] = true;
                }
            }

            $resourceProvidersIds = $resourceProvidersIds ? array_keys($resourceProvidersIds) : $providersIds;

            foreach ($resourceProvidersIds as $providerId) {
                if ($providers->keyExists($providerId)) {
                    /** @var Collection $providerAppointmentList */
                    $providerAppointmentList = $providers->getItem($providerId)->getAppointmentList();

                    /** @var Appointment $appointment */
                    foreach ($fakeAppointments->getItems() as $appointment) {
                        $appointment->setProviderId(new Id($providerId));

                        $providerAppointmentList->addItem(
                            AppointmentFactory::create(
                                [
                                    'bookingStart'       =>
                                        $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s'),
                                    'bookingEnd'         =>
                                        $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
                                    'notifyParticipants' => false,
                                    'serviceId'          => 0,
                                    'providerId'         => $providerId,
                                ]
                            )
                        );
                    }
                }
            }
        }
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * remove available intervals for providers connected to fully used resources that are connected to some locations
     * get providers unavailable intervals for booking on certain locations
     *
     * @param Collection $providersResources
     * @param Collection $appointments
     * @param Collection $providers
     * @param Collection $allLocations
     * @param int        $serviceId
     * @param int|null   $excludeAppointmentId
     * @param int        $personsCount
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function getResourcedDataForLocations(
        $providersResources,
        $appointments,
        $providers,
        $allLocations,
        $serviceId,
        $excludeAppointmentId,
        $personsCount
    ) {
        $result = [];

        /** @var Collection $providerResources */
        foreach ($providersResources->getItems() as $providerId => $providerResources) {
            /** @var Provider $provider */
            $provider = $providers->getItem($providerId);

            $timeZone = $provider->getTimeZone() ?
                new DateTimeZone($provider->getTimeZone()->getValue()) : DateTimeService::getTimeZone();

            /** @var Collection $resourcedAppointments */
            $resourcedAppointments = $this->getResourcedAppointments(
                $providerResources,
                $appointments,
                $excludeAppointmentId
            );

            /** @var Appointment $appointment */
            foreach ($resourcedAppointments->getItems() as $appointment) {
                $appointment->getBookingStart()->getValue()->setTimezone($timeZone);

                $appointment->getBookingEnd()->getValue()->setTimezone($timeZone);
            }

            $resourcedIntervals = $this->getResourcedIntervals(
                $providerResources,
                $resourcedAppointments,
                $appointments,
                $personsCount
            );

            $specialDaysIntervals = $this->scheduleService->getProviderSpecialDayIntervals(
                $provider,
                $allLocations,
                null,
                $serviceId
            );

            $weekDaysIntervals = $this->scheduleService->getProviderWeekDaysIntervals(
                $provider,
                $allLocations,
                null,
                $serviceId
            );

            foreach ($resourcedIntervals as $resourceIndex => $resourceDates) {
                /** @var Resource $resource */
                $resource = $providerResources->getItem($resourceIndex);

                $resourceLocationsIds = [];

                foreach ($resource->getEntities() as $item) {
                    if ($item['entityType'] === 'location') {
                        $resourceLocationsIds[] = $item['entityId'];
                    }
                }

                foreach ($resourceDates as $dateString => $resourceDateIntervals) {
                    $specialDayDateKey = null;

                    foreach ($specialDaysIntervals as $specialDayKey => $specialDays) {
                        if (array_key_exists($dateString, $specialDays['dates'])) {
                            $specialDayDateKey = $specialDayKey;

                            break;
                        }
                    }

                    $freeIntervals = [];

                    if ($specialDayDateKey !== null) {
                        $freeIntervals = $specialDaysIntervals[$specialDayDateKey]['intervals']['free'];
                    } else {
                        $dayIndex = (int)DateTimeService::getDateTimeObjectInTimeZone(
                            $dateString . ' 00:00',
                            $timeZone->getName()
                        )->format('N');

                        if (isset($weekDaysIntervals[$dayIndex])) {
                            $freeIntervals = $weekDaysIntervals[$dayIndex]['free'];
                        }
                    }

                    /** @var Collection $fakeAppointments */
                    $fakeAppointments = new Collection();

                    foreach ($freeIntervals as $freeInterval) {
                        if (!array_diff($freeInterval[2], $resourceLocationsIds)) {
                            $intersectedIntervals = $this->getIntersectedIntervals($resourceDateIntervals, $freeInterval);

                            foreach ($intersectedIntervals as $start => $end) {
                                $timeStart = sprintf('%02d', floor($start / 3600)) . ':'
                                    . sprintf('%02d', floor(($start / 60) % 60));

                                $timeEnd = sprintf('%02d', floor($end / 3600)) . ':'
                                    . sprintf('%02d', floor(($end / 60) % 60));

                                $startDateTime = DateTimeService::getDateTimeObjectInTimeZone(
                                    $dateString . ' ' . $timeStart,
                                    $timeZone->getName()
                                )->setTimezone(DateTimeService::getTimeZone());

                                $endDateTime = DateTimeService::getDateTimeObjectInTimeZone(
                                    $dateString . ' ' . $timeEnd,
                                    $timeZone->getName()
                                )->setTimezone(DateTimeService::getTimeZone());

                                /** @var Appointment $fakeAppointment */
                                $fakeAppointment = AppointmentFactory::create(
                                    [
                                        'bookingStart'       => $startDateTime->format('Y-m-d H:i'),
                                        'bookingEnd'         => $endDateTime->format('Y-m-d H:i'),
                                        'notifyParticipants' => false,
                                        'serviceId'          => 0,
                                        'providerId'         => $provider->getId()->getValue(),
                                    ]
                                );

                                $fakeAppointments->addItem($fakeAppointment);
                            }
                        }
                    }

                    $providerAppointmentsList = $provider->getAppointmentList();

                    /** @var Appointment $appointment */
                    foreach ($fakeAppointments->getItems() as $appointment) {
                        $providerAppointmentsList->addItem($appointment);
                    }

                    $result[$providerId][$dateString][$resourceIndex] = [
                        'locationsIds' => $resourceLocationsIds,
                        'intervals'    => $resourceDateIntervals,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * find intervals where free intervals are intersected with busy intervals
     *
     * @param array $busyIntervals
     * @param array $freeInterval
     *
     * @return array
     */
    private function getIntersectedIntervals($busyIntervals, $freeInterval)
    {
        $intersectedIntervals = [];

        foreach ($busyIntervals as $start => $end) {
            if (
                $end <= $freeInterval[0] &&
                $end > $freeInterval[0] &&
                $end <= $freeInterval[1]
            ) {
                $intersectedIntervals[$freeInterval[0]] = $end;
            } elseif (
                $end >= $freeInterval[0] &&
                $end < $freeInterval[1] &&
                $end >= $freeInterval[1]
            ) {
                $intersectedIntervals[$end] = $freeInterval[1];
            } elseif (
                $end <= $freeInterval[0] &&
                $end >= $freeInterval[1]
            ) {
                $intersectedIntervals[$freeInterval[0]] = $freeInterval[1];
            } elseif (
                $end >= $freeInterval[0] &&
                $end <= $freeInterval[1]
            ) {
                $intersectedIntervals[$end] = $end;
            }
        }

        return $intersectedIntervals;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * set unavailable intervals (fake appointments) to providers in moments when resources are used up
     * return intervals of resources with locations that are used up
     *
     * @param Collection $resources
     * @param Collection $appointments
     * @param Collection $allLocations
     * @param Service    $service
     * @param Collection $providers
     * @param int|null   $locationId
     * @param int|null   $excludeAppointmentId
     * @param int        $personsCount
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function manageResources(
        $resources,
        $appointments,
        $allLocations,
        $service,
        $providers,
        $locationId,
        $excludeAppointmentId,
        $personsCount
    ) {
        /** @var Collection $locationsResources */
        $locationsResources = $this->getLocationsResources($resources);

        /** @var Collection $providersResources */
        $providersResources = $this->getProvidersResources($resources);

        if ($locationsResources->length() && $locationId) {
            /** @var Resource $resource */
            foreach ($locationsResources->getItems() as $resource) {
                foreach ($resource->getEntities() as $item) {
                    if ($item['entityType'] === 'location' && (int)$item['entityId'] === $locationId) {
                        $providersResources->addItem($resource);

                        break 2;
                    }
                }
            }

            $locationsResources = new Collection();
        }

        $this->manageProvidersWithResources(
            $providersResources,
            $appointments,
            $providers,
            $excludeAppointmentId,
            $personsCount
        );

        if ($locationsResources->length()) {
            /** @var Collection $providersLocationsResources */
            $providersLocationsResources = new Collection();

            /** @var Resource $resource */
            foreach ($locationsResources->getItems() as $resourceIndex => $resource) {
                $resourceProvidersIds = [];

                foreach ($resource->getEntities() as $item) {
                    if ($item['entityType'] === 'employee') {
                        $resourceProvidersIds[] = $item['entityId'];
                    }
                }

                if (!$resourceProvidersIds) {
                    $resourceProvidersIds = $providers->keys();
                }

                foreach ($resourceProvidersIds as $providerId) {
                    if ($providers->keyExists($providerId)) {
                        if (!$providersLocationsResources->keyExists($providerId)) {
                            $providersLocationsResources->addItem(new Collection(), $providerId);
                        }

                        $providersLocationsResources->getItem($providerId)->addItem($resource, $resourceIndex);
                    }
                }
            }

            return $this->getResourcedDataForLocations(
                $providersLocationsResources,
                $appointments,
                $providers,
                $allLocations,
                $service->getId()->getValue(),
                $excludeAppointmentId,
                $personsCount
            );
        }

        return [];
    }
}
