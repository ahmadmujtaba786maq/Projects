<?php

namespace AmeliaBooking\Domain\Services\TimeSlot;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\SlotsEntities;
use AmeliaBooking\Domain\Entity\Schedule\DayOff;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Services\Entity\EntityService;
use AmeliaBooking\Domain\Services\Interval\IntervalService;
use AmeliaBooking\Domain\Services\Resource\AbstractResourceService;
use AmeliaBooking\Domain\Services\Schedule\ScheduleService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class TimeSlotService
 *
 * @package AmeliaBooking\Domain\Services\TimeSlot
 */
class TimeSlotService
{
    /** @var IntervalService */
    private $intervalService;

    /** @var ScheduleService */
    private $scheduleService;

    /** @var ProviderService */
    private $providerService;

    /** @var AbstractResourceService */
    private $resourceService;

    /** @var EntityService */
    private $entityService;

    /**
     * TimeSlotService constructor.
     *
     * @param IntervalService $intervalService
     * @param ScheduleService $scheduleService
     * @param ProviderService $providerService
     * @param AbstractResourceService $resourceService
     * @param EntityService   $entityService
     */
    public function __construct(
        IntervalService $intervalService,
        ScheduleService $scheduleService,
        ProviderService $providerService,
        AbstractResourceService $resourceService,
        EntityService $entityService
    ) {
        $this->intervalService = $intervalService;

        $this->scheduleService = $scheduleService;

        $this->providerService = $providerService;

        $this->resourceService = $resourceService;

        $this->entityService = $entityService;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * get appointment intervals for provider.
     *
     * @param array  $weekDaysIntervals
     * @param array  $intervals
     * @param string $dateString
     * @param int    $start
     * @param int    $end
     * @return array
     */
    private function getModifiedEndInterval($weekDaysIntervals, &$intervals, $dateString, $start, $end)
    {
        $dayIndex = DateTimeService::getDayIndex($dateString);

        if (
            isset($weekDaysIntervals[$dayIndex]['busy'][$start]) &&
            $weekDaysIntervals[$dayIndex]['busy'][$start][1] > $end
        ) {
            $end = $weekDaysIntervals[$dayIndex]['busy'][$start][1];
        }

        if (
            isset($intervals[$dateString]['occupied'][$start]) &&
            $intervals[$dateString]['occupied'][$start][1] > $end
        ) {
            $end = $intervals[$dateString]['occupied'][$start][1];
        }

        return $end;
    }

    /**
     * Split start and end in array of dates.
     *
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return array
     */
    private function getPeriodDates($start, $end)
    {
        /** @var DatePeriod $period */
        $period = new DatePeriod(
            $start->setTime(0, 0, 0),
            new DateInterval('P1D'),
            $end
        );

        $periodDates = [];

        /** @var DateTime $date */
        foreach ($period as $index => $date) {
            $periodDates[] = $date->format('Y-m-d');
        }

        return $periodDates;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * get appointment intervals for provider.
     *
     * @param Provider   $provider
     * @param Collection $locations
     * @param int        $serviceId
     * @param int        $locationId
     * @param int        $personsCount
     * @param boolean    $bookIfPending
     * @param boolean    $bookOverApp
     * @param array      $weekDaysIntervals
     * @param array      $specialDaysIntervals
     * @return array
     * @throws InvalidArgumentException
     */
    private function getProviderAppointmentIntervals(
        $provider,
        $locations,
        $serviceId,
        $locationId,
        $personsCount,
        $bookIfPending,
        $bookOverApp,
        &$weekDaysIntervals,
        &$specialDaysIntervals
    ) {
        $intervals = [];

        $specialDays = [];

        foreach ($specialDaysIntervals as $specialDay) {
            $specialDays = array_merge($specialDays, $specialDay['dates']);
        }

        /** @var Appointment $app */
        foreach ($provider->getAppointmentList()->getItems() as $app) {
            $occupiedStart = $provider->getTimeZone() ?
                DateTimeService::getDateTimeObjectInTimeZone(
                    $app->getBookingStart()->getValue()->format('Y-m-d H:i'),
                    $provider->getTimeZone()->getValue()
                ) : DateTimeService::getCustomDateTimeObject($app->getBookingStart()->getValue()->format('Y-m-d H:i'));

            $occupiedEnd = $provider->getTimeZone() ?
                DateTimeService::getDateTimeObjectInTimeZone(
                    $app->getBookingEnd()->getValue()->format('Y-m-d H:i'),
                    $provider->getTimeZone()->getValue()
                ) : DateTimeService::getCustomDateTimeObject($app->getBookingEnd()->getValue()->format('Y-m-d H:i'));

            if ($app->getServiceId()->getValue()) {
                $occupiedStart->modify('-' . ($app->getService()->getTimeBefore() ? $app->getService()->getTimeBefore()->getValue() : 0) . ' seconds');

                $occupiedEnd->modify('+' . ($app->getService()->getTimeAfter() ? $app->getService()->getTimeAfter()->getValue() : 0) . ' seconds');
            }

            $occupiedDateStart = $occupiedStart->format('Y-m-d');

            $occupiedSecondsStart = $this->intervalService->getSeconds($occupiedStart->format('H:i') . ':00');

            $occupiedSecondsEnd = $this->intervalService->getSeconds($occupiedEnd->format('H:i:s'));

            if (
                $occupiedDateStart === $occupiedEnd->format('Y-m-d') &&
                (!$bookOverApp || !$app->getServiceId()->getValue())
            ) {
                $intervals[$occupiedDateStart]['occupied'][$occupiedSecondsStart] = [
                    $occupiedSecondsStart,
                    $this->getModifiedEndInterval(
                        !array_key_exists($occupiedDateStart, $specialDays) ? $weekDaysIntervals : [],
                        $intervals,
                        $occupiedDateStart,
                        $occupiedSecondsStart,
                        $occupiedSecondsEnd
                    )
                ];
            } elseif (!$bookOverApp || !$app->getServiceId()->getValue()) {
                $dates = $this->getPeriodDates($occupiedStart, $occupiedEnd);

                $datesCount = sizeof($dates);

                if ($datesCount === 1) {
                    $intervals[$dates[0]]['occupied'][$occupiedSecondsStart] = [
                        $occupiedSecondsStart,
                        $occupiedSecondsEnd === 0 ? 86400 : $occupiedSecondsEnd
                    ];
                } else {
                    foreach ($dates as $index => $date) {
                        if ($index === 0) {
                            $intervals[$date]['occupied'][$occupiedSecondsStart] = [$occupiedSecondsStart, 86400];
                        } elseif ($index === $datesCount - 1) {
                            $modifiedEnd = $this->getModifiedEndInterval(
                                !array_key_exists($occupiedDateStart, $specialDays) ?
                                    $weekDaysIntervals :
                                    [],
                                $intervals,
                                $date,
                                0,
                                $occupiedSecondsEnd
                            );

                            $intervals[$date]['occupied'][0] = [
                                0,
                                $modifiedEnd === 0 ? 86400 : $modifiedEnd
                            ];
                        } else {
                            $intervals[$date]['occupied'][0] = [0, 86400];
                        }
                    }
                }
            }

            $providerLocationId = $provider->getLocationId() ? $provider->getLocationId()->getValue() : null;

            if ($app->getServiceId()->getValue() === $serviceId) {
                $persons = 0;

                /** @var CustomerBooking $booking */
                foreach ($app->getBookings()->getItems() as $booking) {
                    $persons += $booking->getPersons()->getValue();
                }

                $status = $app->getStatus()->getValue();

                $appLocationId = $app->getLocationId() ? $app->getLocationId()->getValue() : null;

                $hasCapacity =
                    $personsCount !== null &&
                    ($persons + $personsCount) <= $app->getService()->getMaxCapacity()->getValue() &&
                    !($app->isFull() ? $app->isFull()->getValue() : false);

                $hasLocation =
                    !$locationId ||
                    ($app->getLocationId() && $app->getLocationId()->getValue() === $locationId) ||
                    (!$app->getLocationId() && $providerLocationId === $locationId) ||
                    ($appLocationId &&
                        $appLocationId === $locationId &&
                        $locations->getItem($appLocationId)->getStatus()->getValue() === Status::VISIBLE) ||
                    (!$appLocationId && $providerLocationId &&
                        $locations->getItem($providerLocationId)->getStatus()->getValue() === Status::VISIBLE);

                $duration = $app->getBookingStart()->getValue()->diff($app->getBookingEnd()->getValue());

                if (
                    ($hasLocation && $status === BookingStatus::APPROVED && $hasCapacity) ||
                    ($hasLocation && $status === BookingStatus::PENDING && ($bookIfPending || $hasCapacity))
                ) {
                    $endDateTime = $app->getBookingEnd()->getValue()->format('Y-m-d H:i:s');

                    $endDateTimeParts = explode(' ', $endDateTime);

                    $intervals[$occupiedDateStart]['available'][$app->getBookingStart()->getValue()->format('H:i')] =
                        [
                            'locationId' => $app->getLocationId() ?
                                $app->getLocationId()->getValue() : $providerLocationId,
                            'places'     => $app->getService()->getMaxCapacity()->getValue() - $persons,
                            'endDate'    => $endDateTimeParts[0],
                            'endTime'    => $endDateTimeParts[1],
                            'serviceId'  => $serviceId,
                            'duration'   => ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i,
                        ];
                } else {
                    $intervals[$occupiedDateStart]['full'][$app->getBookingStart()->getValue()->format('H:i')] =
                        [
                            'locationId' => $app->getLocationId() ?
                                $app->getLocationId()->getValue() : $providerLocationId,
                            'places'     => $app->getService()->getMaxCapacity()->getValue() - $persons,
                            'end'        => $app->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
                            'serviceId'  => $app->getServiceId()->getValue(),
                            'duration'   => ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i,
                        ];
                }
            } elseif ($app->getServiceId()->getValue()) {
                $duration = $app->getBookingStart()->getValue()->diff($app->getBookingEnd()->getValue());

                $intervals[$occupiedDateStart]['full'][$app->getBookingStart()->getValue()->format('H:i')] =
                    [
                        'locationId' => $app->getLocationId() ?
                            $app->getLocationId()->getValue() : $providerLocationId,
                        'places'     => 0,
                        'end'        => $app->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
                        'serviceId'  => $app->getServiceId()->getValue(),
                        'duration'   => ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i,
                    ];
            }
        }

        return $intervals;
    }

    /**
     * get provider day off dates.
     *
     * @param Provider $provider
     *
     * @return array
     * @throws Exception
     */
    private function getProviderDayOffDates($provider)
    {
        $dates = [];

        /** @var DayOff $dayOff */
        foreach ($provider->getDayOffList()->getItems() as $dayOff) {
            $endDateCopy = clone $dayOff->getEndDate()->getValue();

            $dayOffPeriod = new DatePeriod(
                $dayOff->getStartDate()->getValue(),
                new DateInterval('P1D'),
                $endDateCopy->modify('+1 day')
            );

            /** @var DateTime $date */
            foreach ($dayOffPeriod as $date) {
                $dateFormatted = $dayOff->getRepeat()->getValue() ?
                    $date->format('m-d') :
                    $date->format('Y-m-d');

                $dates[$dateFormatted] = $dateFormatted;
            }
        }

        return $dates;
    }

    /**
     * get available appointment intervals.
     *
     * @param array $availableIntervals
     * @param array $unavailableIntervals
     *
     * @return array
     */
    private function getAvailableIntervals(&$availableIntervals, $unavailableIntervals)
    {
        $parsedAvailablePeriod = [];

        ksort($availableIntervals);
        ksort($unavailableIntervals);

        foreach ($availableIntervals as $available) {
            $parsedAvailablePeriod[] = $available;

            foreach ($unavailableIntervals as $unavailable) {
                if ($parsedAvailablePeriod) {
                    $lastAvailablePeriod = $parsedAvailablePeriod[sizeof($parsedAvailablePeriod) - 1];

                    if ($unavailable[0] >= $lastAvailablePeriod[0] && $unavailable[1] <= $lastAvailablePeriod[1]) {
                        // unavailable interval is inside available interval
                        $fixedPeriod = array_pop($parsedAvailablePeriod);

                        if ($fixedPeriod[0] !== $unavailable[0]) {
                            $parsedAvailablePeriod[] = [$fixedPeriod[0], $unavailable[0], $fixedPeriod[2]];
                        }

                        if ($unavailable[1] !== $fixedPeriod[1]) {
                            $parsedAvailablePeriod[] = [$unavailable[1], $fixedPeriod[1], $fixedPeriod[2]];
                        }
                    } elseif (
                        $unavailable[0] <= $lastAvailablePeriod[0] &&
                        $unavailable[1] >= $lastAvailablePeriod[1]
                    ) {
                        // available interval is inside unavailable interval
                        array_pop($parsedAvailablePeriod);
                    } elseif (
                        $unavailable[0] <= $lastAvailablePeriod[0] &&
                        $unavailable[1] >= $lastAvailablePeriod[0] &&
                        $unavailable[1] <= $lastAvailablePeriod[1]
                    ) {
                        // unavailable interval intersect start of available interval
                        $fixedPeriod = array_pop($parsedAvailablePeriod);

                        if ($unavailable[1] !== $fixedPeriod[1]) {
                            $parsedAvailablePeriod[] = [$unavailable[1], $fixedPeriod[1], $fixedPeriod[2]];
                        }
                    } elseif (
                        $unavailable[0] >= $lastAvailablePeriod[0] &&
                        $unavailable[0] <= $lastAvailablePeriod[1] &&
                        $unavailable[1] >= $lastAvailablePeriod[1]
                    ) {
                        // unavailable interval intersect end of available interval
                        $fixedPeriod = array_pop($parsedAvailablePeriod);

                        if ($fixedPeriod[0] !== $unavailable[0]) {
                            $parsedAvailablePeriod[] = [$fixedPeriod[0], $unavailable[0], $fixedPeriod[2]];
                        }
                    }
                }
            }
        }

        return $parsedAvailablePeriod;
    }

    /**
     * @param Service  $service
     * @param Provider $provider
     * @param int      $personsCount
     *
     * @return bool
     *
     * @throws Exception
     */
    private function getOnlyAppointmentsSlots($service, $provider, $personsCount)
    {
        $getOnlyAppointmentsSlots = false;

        if ($provider->getServiceList()->keyExists($service->getId()->getValue())) {
            /** @var Service $providerService */
            $providerService = $provider->getServiceList()->getItem($service->getId()->getValue());

            if ($personsCount < $providerService->getMinCapacity()->getValue()) {
                $getOnlyAppointmentsSlots = true;
            }
        }

        return $getOnlyAppointmentsSlots;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Service    $service
     * @param int        $locationId
     * @param Collection $providers
     * @param Collection $locations
     * @param array      $globalDaysOffDates
     * @param DateTime   $startDateTime
     * @param DateTime   $endDateTime
     * @param int        $personsCount
     * @param boolean    $bookIfPending
     * @param boolean    $bookIfNotMin
     * @param boolean    $bookAfterMin
     * @param boolean    $bookOverApp
     * @param array      $appointmentsCount
     * @param boolean    $allowAdminBookAtAnytime
     *
     * @return array
     * @throws Exception
     */
    private function getFreeTime(
        Service $service,
        $locationId,
        Collection $locations,
        Collection $providers,
        array $globalDaysOffDates,
        DateTime $startDateTime,
        DateTime $endDateTime,
        $personsCount,
        $bookIfPending,
        $bookIfNotMin,
        $bookAfterMin,
        $bookOverApp,
        $appointmentsCount,
        $allowAdminBookAtAnytime
    ) {

        $weekDayIntervals = [];

        $appointmentIntervals = [];

        $daysOffDates = [];

        $specialDayIntervals = [];

        $getOnlyAppointmentsSlots = [];

        $serviceId = $service->getId()->getValue();

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $providerId = $provider->getId()->getValue();

            $getOnlyAppointmentsSlots[$providerId] = $bookIfNotMin && $bookAfterMin ? $this->getOnlyAppointmentsSlots(
                $service,
                $provider,
                $personsCount
            ) : false;

            $daysOffDates[$providerId] = $this->getProviderDayOffDates($provider);

            $weekDayIntervals[$providerId] = $this->scheduleService->getProviderWeekDaysIntervals(
                $provider,
                $locations,
                $locationId,
                $serviceId
            );

            $specialDayIntervals[$providerId] = $this->scheduleService->getProviderSpecialDayIntervals(
                $provider,
                $locations,
                $locationId,
                $serviceId
            );

            $appointmentIntervals[$providerId] = $this->getProviderAppointmentIntervals(
                $provider,
                $locations,
                $serviceId,
                $locationId,
                $personsCount,
                $bookIfPending,
                $bookOverApp,
                $weekDayIntervals[$providerId],
                $specialDayIntervals[$providerId]
            );
        }

        $freeDateIntervals = [];

        foreach ($appointmentIntervals as $providerKey => $providerDates) {
            foreach ((array)$providerDates as $dateKey => $dateIntervals) {
                $dayIndex = DateTimeService::getDayIndex($dateKey);

                $specialDayDateKey = null;

                foreach ((array)$specialDayIntervals[$providerKey] as $specialDayKey => $specialDays) {
                    if (array_key_exists($dateKey, $specialDays['dates'])) {
                        $specialDayDateKey = $specialDayKey;
                        break;
                    }
                }

                if (
                    $specialDayDateKey !== null &&
                    isset($specialDayIntervals[$providerKey][$specialDayDateKey]['intervals']['free'])
                ) {
                    // get free intervals if it is special day
                    $freeDateIntervals[$providerKey][$dateKey] = $this->getAvailableIntervals(
                        $specialDayIntervals[$providerKey][$specialDayDateKey]['intervals']['free'],
                        !empty($dateIntervals['occupied']) ? $dateIntervals['occupied'] : []
                    );
                } elseif (
                    isset($weekDayIntervals[$providerKey][$dayIndex]['free']) &&
                    !isset($specialDayIntervals[$providerKey][$specialDayDateKey]['intervals'])
                ) {
                    // get free intervals if it is working day
                    $unavailableIntervals =
                        $weekDayIntervals[$providerKey][$dayIndex]['busy'] + (!empty($dateIntervals['occupied']) ? $dateIntervals['occupied'] : []);

                    $intersectedTimes = array_intersect(
                        array_keys($weekDayIntervals[$providerKey][$dayIndex]['busy']),
                        array_keys(!empty($dateIntervals['occupied']) ? $dateIntervals['occupied'] : [])
                    );

                    foreach ($intersectedTimes as $time) {
                        $unavailableIntervals[$time] =
                            $weekDayIntervals[$providerKey][$dayIndex]['busy'][$time] >
                            $dateIntervals['occupied'][$time] ?
                                $weekDayIntervals[$providerKey][$dayIndex]['busy'][$time] :
                                $dateIntervals['occupied'][$time];
                    }

                    $freeDateIntervals[$providerKey][$dateKey] = $this->getAvailableIntervals(
                        $weekDayIntervals[$providerKey][$dayIndex]['free'],
                        $unavailableIntervals ?: []
                    );
                }
            }
        }

        $startDateTime = clone $startDateTime;

        $startDateTime->setTime(0, 0);

        $endDateTime = clone $endDateTime;

        $endDateTime->modify('+1 day')->setTime(0, 0);

        // create calendar
        $period = new DatePeriod(
            $startDateTime,
            new DateInterval('P1D'),
            $endDateTime
        );

        $calendar = [];

        /** @var DateTime $day */
        foreach ($period as $day) {
            $currentDate = $day->format('Y-m-d');
            $dayIndex    = (int)$day->format('N');

            $isGlobalDayOff = array_key_exists($currentDate, $globalDaysOffDates) ||
                array_key_exists($day->format('m-d'), $globalDaysOffDates);

            if (!$isGlobalDayOff) {
                foreach ($weekDayIntervals as $providerKey => $providerWorkingHours) {
                    $isProviderDayOff = array_key_exists($currentDate, $daysOffDates[$providerKey]) ||
                        array_key_exists($day->format('m-d'), $daysOffDates[$providerKey]);

                    $specialDayDateKey = null;

                    foreach ((array)$specialDayIntervals[$providerKey] as $specialDayKey => $specialDays) {
                        if (array_key_exists($currentDate, $specialDays['dates'])) {
                            $specialDayDateKey = $specialDayKey;
                            break;
                        }
                    }

                    if (!$isProviderDayOff) {
                        // daily limit per employee
                        if (
                            !$allowAdminBookAtAnytime &&
                            !empty($appointmentsCount['limitCount']) &&
                            !empty($appointmentsCount['appCount'][$providerKey][$currentDate]) &&
                            $appointmentsCount['appCount'][$providerKey][$currentDate] >= $appointmentsCount['limitCount']
                        ) {
                            continue;
                        }

                        if ($freeDateIntervals && isset($freeDateIntervals[$providerKey][$currentDate])) {
                            // get date intervals if there are appointments (special or working day)
                            $calendar[$currentDate][$providerKey] = [
                                'slots'     => $personsCount && $bookIfNotMin && isset($appointmentIntervals[$providerKey][$currentDate]['available']) ?
                                    $appointmentIntervals[$providerKey][$currentDate]['available'] : [],
                                'full'      => isset($appointmentIntervals[$providerKey][$currentDate]['full']) ?
                                    $appointmentIntervals[$providerKey][$currentDate]['full'] : [],
                                'intervals' => $getOnlyAppointmentsSlots[$providerKey] ? [] : $freeDateIntervals[$providerKey][$currentDate],
                                'count' => !empty($appointmentsCount[$providerKey][$currentDate]) ? $appointmentsCount[$providerKey][$currentDate] : 0
                            ];
                        } else {
                            if ($specialDayDateKey !== null && isset($specialDayIntervals[$providerKey][$specialDayDateKey]['intervals']['free'])) {
                                // get date intervals if it is special day with out appointments
                                $calendar[$currentDate][$providerKey] = [
                                    'slots'     => [],
                                    'full'      => [],
                                    'intervals' => $getOnlyAppointmentsSlots[$providerKey] ?
                                        [] :
                                        $specialDayIntervals[$providerKey][$specialDayDateKey]['intervals']['free'],
                                    'count' => !empty($appointmentsCount[$providerKey][$currentDate]) ?
                                        $appointmentsCount[$providerKey][$currentDate] :
                                        0
                                ];
                            } elseif (
                                isset($weekDayIntervals[$providerKey][$dayIndex]) &&
                                !isset($specialDayIntervals[$providerKey][$specialDayDateKey]['intervals'])
                            ) {
                                // get date intervals if it is working day without appointments
                                $calendar[$currentDate][$providerKey] = [
                                    'slots'     => [],
                                    'full'      => [],
                                    'intervals' => $getOnlyAppointmentsSlots[$providerKey] ?
                                        [] :
                                        $weekDayIntervals[$providerKey][$dayIndex]['free'],
                                    'count' => !empty($appointmentsCount[$providerKey][$currentDate]) ?
                                        $appointmentsCount[$providerKey][$currentDate] :
                                        0
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $calendar;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Service  $service
     * @param int      $requiredTime
     * @param array    $freeIntervals
     * @param array    $resourcedIntervals
     * @param int      $slotLength
     * @param DateTime $startDateTime
     * @param bool     $serviceDurationAsSlot
     * @param bool     $bufferTimeInSlot
     * @param String   $timeZone
     * @param bool     $structured
     * @param array    $customPricing
     *
     * @return array
     * @throws Exception
     */
    private function getAppointmentFreeSlots(
        $service,
        $requiredTime,
        &$freeIntervals,
        $resourcedIntervals,
        $slotLength,
        $startDateTime,
        $serviceDurationAsSlot,
        $bufferTimeInSlot,
        $timeZone,
        $structured,
        $customPricing
    ) {
        $availableResult = [];

        $occupiedResult = [];

        $realRequiredTime = $requiredTime -
            $service->getTimeBefore()->getValue() -
            $service->getTimeAfter()->getValue();

        if ($serviceDurationAsSlot && !$bufferTimeInSlot) {
            $requiredTime = $requiredTime -
                $service->getTimeBefore()->getValue() -
                $service->getTimeAfter()->getValue();
        }

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $currentDateString = $currentDateTime->format('Y-m-d');

        $currentTimeStringInSeconds = $this->intervalService->getSeconds($currentDateTime->format('H:i:s'));

        $currentTimeInSeconds = $this->intervalService->getSeconds($currentDateTime->format('H:i:s'));

        $currentDateFormatted = $currentDateTime->format('Y-m-d');

        $startTimeInSeconds = $this->intervalService->getSeconds($startDateTime->format('H:i:s'));

        $startDateFormatted = $startDateTime->format('Y-m-d');

        $bookingLength = $serviceDurationAsSlot ? $requiredTime : $slotLength;

        $appCount = [];

        $isContinuousTime = false;

        $continuousTimeSlot = null;

        foreach ($freeIntervals as $dateKey => $dateProviders) {
            foreach ((array)$dateProviders as $providerKey => $provider) {
                foreach ((array)$provider['intervals'] as $timePeriod) {
                    $moveStart = false;

                    if ($timePeriod[0] === 0 && $isContinuousTime && $continuousTimeSlot !== null) {
                        $isContinuousTime = false;

                        $moveStart = true;
                    }

                    if ($timePeriod[1] === 86400) {
                        $nextDateString = DateTimeService::getDateTimeObjectInTimeZone(
                            $dateKey . ' 00:00:00',
                            $timeZone
                        )->modify('+1 days')->format('Y-m-d');

                        if (
                            isset($freeIntervals[$nextDateString][$providerKey]['intervals'][0]) &&
                            $freeIntervals[$nextDateString][$providerKey]['intervals'][0][0] === 0
                        ) {
                            $isContinuousTime = true;

                            $nextDayInterval = $freeIntervals[$nextDateString][$providerKey]['intervals'][0][1];

                            $timePeriod[1] += (
                            $realRequiredTime + $service->getTimeAfter()->getValue() <= $nextDayInterval
                                ? $realRequiredTime + $service->getTimeAfter()->getValue()
                                : $nextDayInterval);
                        }
                    }

                    if ($serviceDurationAsSlot && !$bufferTimeInSlot) {
                        $timePeriod[1] = $timePeriod[1] - $service->getTimeAfter()->getValue();
                    }

                    $customerTimeStart = $timePeriod[0] + (!$moveStart ? $service->getTimeBefore()->getValue() : 0);

                    $providerTimeStart = $customerTimeStart - (!$moveStart ? $service->getTimeBefore()->getValue() : 0);

                    $numberOfSlots = (int)(
                        floor(
                            (
                                $timePeriod[1] -
                                $providerTimeStart -
                                ($requiredTime - ($moveStart ? $service->getTimeBefore()->getValue() : 0))
                            ) / $bookingLength
                        ) + 1
                    );

                    $inspectResourceIndexes = [];

                    if (isset($resourcedIntervals[$dateKey])) {
                        foreach ($resourcedIntervals[$dateKey] as $resourceIndex => $resourceData) {
                            if (
                                array_intersect(
                                    $timePeriod[2],
                                    $resourcedIntervals[$dateKey][$resourceIndex]['locationsIds']
                                )
                            ) {
                                $inspectResourceIndexes[] = $resourceIndex;
                            }
                        }
                    }

                    $providerPeriodSlots = [];

                    $achievedLength = 0;

                    if ($moveStart && $continuousTimeSlot !== 86400 && ($bookingLength - (86400 - $continuousTimeSlot)) >= 0) {
                        $customerTimeStart += $bookingLength - (86400 - $continuousTimeSlot);

                        $providerTimeStart += $bookingLength - (86400 - $continuousTimeSlot);

                        $numberOfSlots = (int)(floor(($timePeriod[1] - $providerTimeStart - $requiredTime) / $bookingLength) + 1);
                    }

                    if ($moveStart) {
                        $continuousTimeSlot = null;
                    }

                    for ($i = 0; $i < $numberOfSlots; $i++) {
                        $achievedLength += $bookingLength;

                        $timeSlot = $customerTimeStart + $i * $bookingLength;

                        if (
                            (
                                $startDateFormatted !== $dateKey &&
                                ($serviceDurationAsSlot &&
                                !$bufferTimeInSlot ? $timeSlot <= $timePeriod[1] - $requiredTime : true)
                            ) ||
                            ($startDateFormatted === $dateKey && $startTimeInSeconds < $timeSlot) ||
                            ($startDateFormatted ===
                                $currentDateFormatted &&
                                $startDateFormatted === $dateKey &&
                                $startTimeInSeconds < $timeSlot &&
                                $currentTimeInSeconds < $timeSlot)
                        ) {
                            $timeSlotEnd = $timeSlot + $bookingLength;

                            $filteredLocationsIds = $timePeriod[2];

                            foreach ($inspectResourceIndexes as $resourceIndex) {
                                foreach ($resourcedIntervals[$dateKey][$resourceIndex]['intervals'] as $start => $end) {
                                    if (
                                        ($start >= $timeSlot && $start < $timeSlotEnd) ||
                                        ($end > $timeSlot && $end <= $timeSlotEnd) ||
                                        ($start <= $timeSlot && $end >= $timeSlotEnd) ||
                                        ($start >= $timeSlot && $start < $timeSlot + $requiredTime)
                                    ) {
                                        $filteredLocationsIds = array_diff(
                                            $filteredLocationsIds,
                                            $resourcedIntervals[$dateKey][$resourceIndex]['locationsIds']
                                        );

                                        if (!$filteredLocationsIds) {
                                            if ($achievedLength < $requiredTime) {
                                                $providerPeriodSlots = [];

                                                $achievedLength = 0;
                                            }

                                            continue 3;
                                        }

                                        $removedLocationsIds = array_diff(
                                            $resourcedIntervals[$dateKey][$resourceIndex]['locationsIds'],
                                            $filteredLocationsIds
                                        );

                                        if ($removedLocationsIds && $achievedLength < $requiredTime) {
                                            $parsedPeriodSlots = [];

                                            foreach ($providerPeriodSlots as $previousTimeSlot => $periodSlotData) {
                                                if (
                                                    $start >= $previousTimeSlot &&
                                                    $start < $previousTimeSlot + $requiredTime
                                                ) {
                                                    foreach ($periodSlotData as $data) {
                                                        if (!in_array($data[1], $removedLocationsIds)) {
                                                            $parsedPeriodSlots[$previousTimeSlot][] = $data;
                                                        }
                                                    }
                                                } else {
                                                    $parsedPeriodSlots[$previousTimeSlot] = $periodSlotData;
                                                }
                                            }

                                            $providerPeriodSlots = $parsedPeriodSlots;
                                        }
                                    }
                                }
                            }

                            if (!$timePeriod[2]) {
                                $providerPeriodSlots[$timeSlot][] = [$providerKey, null];
                            } elseif ($filteredLocationsIds) {
                                foreach ($filteredLocationsIds as $locationId) {
                                    $providerPeriodSlots[$timeSlot][] = [$providerKey, $locationId];
                                }
                            }
                        }
                    }

                    foreach ($providerPeriodSlots as $timeSlot => $data) {
                        $time = sprintf('%02d', floor($timeSlot / 3600)) . ':'
                            . sprintf('%02d', floor(($timeSlot / 60) % 60));

                        if ($time !== '24:00') {
                            if (!$structured) {
                                $availableResult[$dateKey][$time] = $data;
                            } else {
                                foreach ($data as $item) {
                                    $availableResult[$dateKey][$time][] = [
                                        'e' => $item[0],
                                        'l' => $item[1],
                                        'p' => $customPricing
                                            ? $this->providerService->getDateTimePrice(
                                                $customPricing,
                                                $dateKey,
                                                $timeSlot,
                                                $timeZone
                                            )
                                            : null,
                                    ];
                                }
                            }

                            if ($isContinuousTime) {
                                $continuousTimeSlot = $timeSlot;
                            }
                        }
                    }
                }

                foreach ($provider['slots'] as $appointmentTime => $appointmentData) {
                    $startInSeconds = $this->intervalService->getSeconds($appointmentTime . ':00');

                    if (
                        $currentDateString === $dateKey &&
                        ($currentTimeStringInSeconds > $startInSeconds || $startTimeInSeconds > $startInSeconds)
                    ) {
                        continue;
                    }

                    $endInSeconds = $this->intervalService->getSeconds($appointmentData['endTime']) + $service->getTimeAfter()->getValue();

                    $newEndInSeconds = $startInSeconds + $realRequiredTime;

                    if (
                        $newEndInSeconds !== 86400 &&
                        ($newEndInSeconds > 86400 ? $newEndInSeconds - 86400 > $endInSeconds : $newEndInSeconds > $endInSeconds)
                    ) {
                        if ($dateKey !== $appointmentData['endDate']) {
                            $nextDateString = DateTimeService::getDateTimeObjectInTimeZone(
                                $dateKey . ' 00:00:00',
                                $timeZone
                            )->modify('+1 days')->format('Y-m-d');

                            if (
                                !isset($freeIntervals[$nextDateString][$providerKey]['intervals'][0]) ||
                                $freeIntervals[$nextDateString][$providerKey]['intervals'][0][0] != $endInSeconds ||
                                $freeIntervals[$nextDateString][$providerKey]['intervals'][0][1] < $newEndInSeconds - 86400
                            ) {
                                continue;
                            }
                        } elseif ($newEndInSeconds > 86400) {
                            $nextIntervalIsValid = false;

                            foreach ($freeIntervals[$dateKey][$providerKey]['intervals'] as $interval) {
                                if ($interval[0] === $endInSeconds && $interval[1] === 86400) {
                                    $nextIntervalIsValid = true;

                                    break;
                                }
                            }

                            if (!$nextIntervalIsValid) {
                                continue;
                            }

                            $nextDateString = DateTimeService::getDateTimeObjectInTimeZone(
                                $dateKey . ' 00:00:00',
                                $timeZone
                            )->modify('+1 days')->format('Y-m-d');

                            if (
                                !isset($freeIntervals[$nextDateString][$providerKey]['intervals'][0]) ||
                                $freeIntervals[$nextDateString][$providerKey]['intervals'][0][0] != 0 ||
                                $freeIntervals[$nextDateString][$providerKey]['intervals'][0][1] < $newEndInSeconds - 86400
                            ) {
                                continue;
                            }
                        } else {
                            $nextIntervalIsValid = false;

                            foreach ($freeIntervals[$dateKey][$providerKey]['intervals'] as $interval) {
                                if ($interval[0] === $endInSeconds && $interval[1] >= $newEndInSeconds) {
                                    $nextIntervalIsValid = true;

                                    break;
                                }
                            }

                            if (!$nextIntervalIsValid) {
                                continue;
                            }
                        }
                    }

                    $availableResult[$dateKey][$appointmentTime] = [
                        !$structured ? [
                            $providerKey,
                            $appointmentData['locationId'],
                            $appointmentData['places'],
                            $appointmentData['serviceId'],
                            $appointmentData['duration'],
                        ] : [
                            'e' => $providerKey,
                            'l' => $appointmentData['locationId'],
                            'c' => $appointmentData['places'],
                            's' => $appointmentData['serviceId'],
                            'd' => $appointmentData['duration'],
                            'p' => $customPricing
                                ? $this->providerService->getDateTimePrice(
                                    $customPricing,
                                    $dateKey,
                                    $this->intervalService->getSeconds($appointmentTime),
                                    $timeZone
                                )
                                : null,
                        ]
                    ];
                }

                foreach ($provider['full'] as $appointmentTime => $appointmentData) {
                    $occupiedResult[$dateKey][$appointmentTime][] = !$structured ? [
                        $providerKey,
                        $appointmentData['locationId'],
                        $appointmentData['places'],
                        $appointmentData['serviceId'],
                        $appointmentData['duration'],
                    ] : [
                        'e' => $providerKey,
                        'l' => $appointmentData['locationId'],
                        'c' => $appointmentData['places'],
                        's' => $appointmentData['serviceId'],
                        'd' => $appointmentData['duration'],
                    ];
                }

                $appCount[$dateKey] = $freeIntervals[$dateKey][$providerKey]['count'];
            }
        }

        return [
            'available' => $availableResult,
            'occupied'  => $occupiedResult,
            'appCount'  => $appCount
        ];
    }

    /**
     * @param array  $slots
     * @param string $timeZone
     *
     * @return array
     * @throws Exception
     */
    private function getSlotsInMainTimeZoneFromTimeZone($slots, $timeZone)
    {
        $convertedProviderSlots = [];

        foreach ($slots as $slotDate => $slotTimes) {
            foreach ($slots[$slotDate] as $slotTime => $slotTimesProviders) {
                $convertedSlotParts = explode(
                    ' ',
                    DateTimeService::getDateTimeObjectInTimeZone(
                        $slotDate . ' ' . $slotTime,
                        $timeZone
                    )->setTimezone(new DateTimeZone(DateTimeService::getTimeZone()->getName()))->format('Y-m-d H:i')
                );

                $convertedProviderSlots[$convertedSlotParts[0]][$convertedSlotParts[1]] = $slotTimesProviders;
            }
        }

        return $convertedProviderSlots;
    }


    /**
     * @param Collection $appointments
     * @param int $excludeAppointmentId
     *
     * @return array
     * @throws Exception
     */
    public function getAppointmentCount($appointments, $excludeAppointmentId)
    {
        $appCount = [];

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            if (!$excludeAppointmentId || empty($appointment->getId()) || $appointment->getId()->getValue() !== $excludeAppointmentId) {
                if (!empty($appCount[$appointment->getProviderId()->getValue()][$appointment->getBookingStart()->getValue()->format('Y-m-d')])) {
                    $appCount[$appointment->getProviderId()->getValue()][$appointment->getBookingStart()->getValue()->format('Y-m-d')]++;
                } else {
                    $appCount[$appointment->getProviderId()->getValue()][$appointment->getBookingStart()->getValue()->format('Y-m-d')] = 1;
                }
            }
        }

        return $appCount;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array         $settings
     * @param array         $props
     * @param SlotsEntities $slotsEntities
     * @param Collection    $appointments
     *
     * @return array
     * @throws Exception
     */
    public function getSlots($settings, $props, $slotsEntities, $appointments)
    {
        $appointmentsCount = $this->getAppointmentCount($appointments, $props['excludeAppointmentId']);

        $resourcedLocationsIntervals = $slotsEntities->getResources()->length() ?
            $this->resourceService->manageResources(
                $slotsEntities->getResources(),
                $appointments,
                $slotsEntities->getLocations(),
                $slotsEntities->getServices()->getItem($props['serviceId']),
                $slotsEntities->getProviders(),
                $props['locationId'],
                $props['excludeAppointmentId'],
                array_key_exists('totalPersons', $props) ? $props['totalPersons'] : $props['personsCount']
            ) : [];

        $this->entityService->filterSlotsAppointments($slotsEntities, $appointments, $props);

        $this->providerService->addAppointmentsToAppointmentList(
            $slotsEntities->getProviders(),
            $appointments,
            $settings['isGloballyBusySlot']
        );

        return $this->getCalculatedFreeSlots(
            $settings,
            $props,
            $slotsEntities,
            $resourcedLocationsIntervals,
            $appointmentsCount
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array         $settings
     * @param array         $props
     * @param SlotsEntities $slotsEntities
     * @param array         $resourcedLocationsIntervals
     * @param array         $appointmentsCount
     *
     * @return array
     * @throws Exception
     */
    private function getCalculatedFreeSlots(
        $settings,
        $props,
        $slotsEntities,
        $resourcedLocationsIntervals,
        $appointmentsCount
    ) {
        $freeProvidersSlots = [];

        /** @var DateTime $startDateTime */
        $startDateTime = $props['startDateTime'];

        /** @var DateTime $endDateTime */
        $endDateTime = $props['endDateTime'];

        /** @var Service $service */
        $service = $slotsEntities->getServices()->getItem($props['serviceId']);

        /** @var Collection $providers */
        $providers = $slotsEntities->getProviders();

        /** @var Collection $locations */
        $locations = $slotsEntities->getLocations();

        $requiredTime = $this->entityService->getAppointmentRequiredTime(
            $service,
            $props['extras']
        );

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            /** @var Service $providerService */
            $providerService = $service;

            if ($provider->getServiceList()->keyExists($service->getId()->getValue())) {
                $providerService = $provider->getServiceList()->getItem($service->getId()->getValue());

                if ($providerService && $props['personsCount'] > $providerService->getMaxCapacity()->getValue()) {
                    continue;
                }
            }

            $customPricing = $this->providerService->getCustomPricing(
                $providerService,
                $provider->getTimeZone()
                    ? $provider->getTimeZone()->getValue()
                    : DateTimeService::getTimeZone()->getName()
            );

            $providerContainer = new Collection();

            if ($provider->getTimeZone()) {
                $this->providerService->modifyProviderTimeZone(
                    $provider,
                    $settings['allowAdminBookAtAnyTime'] ? [] : $settings['globalDaysOff'],
                    $startDateTime,
                    $endDateTime
                );
            }

            $start = $provider->getTimeZone() ?
                DateTimeService::getCustomDateTimeObjectInTimeZone(
                    $startDateTime->format('Y-m-d H:i'),
                    $provider->getTimeZone()->getValue()
                ) : DateTimeService::getCustomDateTimeObject($startDateTime->format('Y-m-d H:i'));

            $end = $provider->getTimeZone() ?
                DateTimeService::getCustomDateTimeObjectInTimeZone(
                    $endDateTime->format('Y-m-d H:i'),
                    $provider->getTimeZone()->getValue()
                ) : DateTimeService::getCustomDateTimeObject($endDateTime->format('Y-m-d H:i'));

            $providerContainer->addItem($provider, $provider->getId()->getValue());

            $limitPerEmployee = !empty($settings['limitPerEmployee']) && !empty($settings['limitPerEmployee']['enabled']) ?
                $settings['limitPerEmployee']['numberOfApp'] : null;

            $freeIntervals = $this->getFreeTime(
                $service,
                $props['locationId'],
                $locations,
                $providerContainer,
                $settings['allowAdminBookAtAnyTime'] || $provider->getTimeZone() ?
                    [] : $settings['globalDaysOff'],
                $start,
                $end,
                $props['personsCount'],
                $props['isFrontEndBooking'] && $settings['allowBookingIfPending'] && $settings['defaultAppointmentStatus'] === BookingStatus::PENDING,
                $settings['allowBookingIfNotMin'],
                $props['isFrontEndBooking'] ? $settings['openedBookingAfterMin'] : false,
                !empty($settings['allowAdminBookOverApp']),
                ['limitCount' => $limitPerEmployee, 'appCount' => $appointmentsCount],
                !empty($settings['allowAdminBookAtAnyTime'])
            );

            $freeProvidersSlots[$provider->getId()->getValue()] = $this->getAppointmentFreeSlots(
                $service,
                $requiredTime,
                $freeIntervals,
                !empty($resourcedLocationsIntervals[$provider->getId()->getValue()])
                    ? $resourcedLocationsIntervals[$provider->getId()->getValue()] : [],
                $settings['timeSlotLength'] ?: $requiredTime,
                $start,
                $settings['allowAdminBookAtAnyTime'] ? $settings['adminServiceDurationAsSlot'] :
                    $settings['serviceDurationAsSlot'],
                $settings['bufferTimeInSlot'],
                $provider->getTimeZone() ?
                    $provider->getTimeZone()->getValue() : DateTimeService::getTimeZone()->getName(),
                !empty($props['structured']),
                $customPricing
            );
        }

        $freeSlots = [
            'available'               => [],
            'occupied'                => [],
            'appCount'                => [],
            'duration'                => $requiredTime / 60,
        ];

        foreach ($freeProvidersSlots as $providerKey => $providerSlots) {
            /** @var Provider $provider */
            $provider = $providers->getItem($providerKey);

            $freeSlots['appCount'][$providerKey] = $providerSlots['appCount'];

            foreach (['available', 'occupied'] as $type) {
                if ($provider->getTimeZone()) {
                    $providerSlots[$type] = $this->getSlotsInMainTimeZoneFromTimeZone(
                        $providerSlots[$type],
                        $provider->getTimeZone()->getValue()
                    );
                }

                foreach ($providerSlots[$type] as $dateKey => $dateSlots) {
                    foreach ($dateSlots as $timeKey => $slotData) {
                        if (empty($freeSlots[$type][$dateKey][$timeKey])) {
                            $freeSlots[$type][$dateKey][$timeKey] = [];
                        }

                        foreach ($slotData as $item) {
                            $freeSlots[$type][$dateKey][$timeKey][] = $item;
                        }

                        if (isset($freeSlots[$type][$dateKey])) {
                            if (!$freeSlots[$type][$dateKey]) {
                                unset($freeSlots[$type][$dateKey]);
                            } else {
                                ksort($freeSlots[$type][$dateKey]);
                            }
                        }
                    }
                }
            }
        }

        return $freeSlots;
    }
}
