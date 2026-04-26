<?php

namespace AmeliaBooking\Domain\Services\Schedule;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Schedule\Period;
use AmeliaBooking\Domain\Entity\Schedule\PeriodService;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriod;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriodService;
use AmeliaBooking\Domain\Entity\Schedule\TimeOut;
use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\Interval\IntervalService;
use AmeliaBooking\Domain\Services\Location\LocationService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use DateTime;
use DateInterval;
use DatePeriod;
use Exception;

/**
 * Class ScheduleService
 *
 * @package AmeliaBooking\Domain\Services\Schedule
 */
class ScheduleService
{
    /** @var IntervalService */
    private $intervalService;

    /** @var ProviderService */
    private $providerService;

    /** @var LocationService */
    private $locationService;

    /**
     * ScheduleService constructor.
     *
     * @param IntervalService $intervalService
     * @param ProviderService $providerService
     * @param LocationService $locationService
     */
    public function __construct(
        $intervalService,
        $providerService,
        $locationService
    ) {
        $this->intervalService = $intervalService;

        $this->providerService = $providerService;

        $this->locationService = $locationService;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * get special dates intervals for provider.
     *
     * @param Provider   $provider
     * @param Collection $locations
     * @param int|null   $locationId
     * @param int        $serviceId
     *
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getProviderSpecialDayIntervals($provider, $locations, $locationId, $serviceId)
    {
        $intervals = [];

        $hasVisibleLocations = $this->locationService->hasVisibleLocations($locations);

        /** @var Location $providerLocation */
        $providerLocation = $provider->getLocationId() &&
        $locations->length() &&
        $locations->keyExists($provider->getLocationId()->getValue()) ?
            $locations->getItem($provider->getLocationId()->getValue()) : null;

        /** @var SpecialDay $specialDay */
        foreach ($provider->getSpecialDayList()->getItems() as $specialDay) {
            $specialDates = [];

            $endDateCopy = clone $specialDay->getEndDate()->getValue();

            $specialDaysPeriod = new DatePeriod(
                $specialDay->getStartDate()->getValue(),
                new DateInterval('P1D'),
                $endDateCopy->modify('+1 day')
            );

            /** @var DateTime $day */
            foreach ($specialDaysPeriod as $day) {
                $specialDates[$day->format('Y-m-d')] = true;
            }

            $specialDatesIntervals = [];

            /** @var SpecialDayPeriod $period */
            foreach ($specialDay->getPeriodList()->getItems() as $period) {
                /** @var Collection $availablePeriodLocations */
                $availablePeriodLocations = $this->providerService->getProviderPeriodLocations(
                    $period,
                    $providerLocation,
                    $locations,
                    $hasVisibleLocations
                );

                if (
                    ($hasVisibleLocations && !$availablePeriodLocations->length()) ||
                    ($hasVisibleLocations && $locationId && !$availablePeriodLocations->keyExists($locationId))
                ) {
                    continue;
                }

                $hasService = $period->getPeriodServiceList()->length() === 0;

                /** @var SpecialDayPeriodService $periodService */
                foreach ($period->getPeriodServiceList()->getItems() as $periodService) {
                    if ($periodService->getServiceId()->getValue() === $serviceId) {
                        $hasService = true;
                    }
                }

                $start = $this->intervalService->getSeconds($period->getStartTime()->getValue()->format('H:i:s'));

                $end = $this->intervalService->getSeconds($this->intervalService->getEndTimeString($period->getEndTime()->getValue()->format('H:i:s')));

                if ($hasService) {
                    $specialDatesIntervals['free'][$start] = [
                        $start,
                        $end,
                        $locationId ? [$locationId] : $availablePeriodLocations->keys(),
                    ];
                }
            }

            $intervals[] = [
                'dates'     => $specialDates,
                'intervals' => $specialDatesIntervals
            ];
        }

        return array_reverse($intervals);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * get week days intervals for provider.
     *
     * @param Provider   $provider
     * @param Collection $locations
     * @param int        $serviceId
     * @param int|null   $locationId
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getProviderWeekDaysIntervals($provider, $locations, $locationId, $serviceId)
    {
        $intervals = [];

        $hasVisibleLocations = $this->locationService->hasVisibleLocations($locations);

        /** @var Location $providerLocation */
        $providerLocation = $provider->getLocationId() && $locations->length()
            && $locations->keyExists($provider->getLocationId()->getValue()) ?
            $locations->getItem($provider->getLocationId()->getValue()) : null;

        $providerLocationId = $providerLocation ? $providerLocation->getId()->getValue() : null;

        /** @var WeekDay $weekDay */
        foreach ($provider->getWeekDayList()->getItems() as $weekDay) {
            $dayIndex = $weekDay->getDayIndex()->getValue();

            $intervals[$dayIndex]['busy'] = [];
            $intervals[$dayIndex]['free'] = [];

            /** @var TimeOut $timeOut */
            foreach ($weekDay->getTimeOutList()->getItems() as $timeOut) {
                $start = $this->intervalService->getSeconds($timeOut->getStartTime()->getValue()->format('H:i:s'));

                $intervals[$dayIndex]['busy'][$start] = [
                    $start,
                    $this->intervalService->getSeconds($timeOut->getEndTime()->getValue()->format('H:i:s'))
                ];
            }

            /** @var Period $period */
            foreach ($weekDay->getPeriodList()->getItems() as $period) {
                /** @var Collection $availablePeriodLocations */
                $availablePeriodLocations = $this->providerService->getProviderPeriodLocations(
                    $period,
                    $providerLocation,
                    $locations,
                    $hasVisibleLocations
                );

                if (
                    ($hasVisibleLocations && !$availablePeriodLocations->length()) ||
                    ($hasVisibleLocations && $locationId && !$availablePeriodLocations->keyExists($locationId))
                ) {
                    continue;
                }

                $hasService = $period->getPeriodServiceList()->length() === 0;

                /** @var PeriodService $periodService */
                foreach ($period->getPeriodServiceList()->getItems() as $periodService) {
                    if ($periodService->getServiceId()->getValue() === $serviceId) {
                        $hasService = true;
                    }
                }

                $start = $this->intervalService->getSeconds($period->getStartTime()->getValue()->format('H:i:s'));

                $end = $this->intervalService->getSeconds($this->intervalService->getEndTimeString($period->getEndTime()->getValue()->format('H:i:s')));

                if ($hasService) {
                    $intervals[$dayIndex]['free'][$start] = [
                        $start,
                        $end,
                        $locationId ? [$locationId] : $availablePeriodLocations->keys(),
                    ];
                }
            }

            if ($weekDay->getPeriodList()->length() === 0) {
                $start = $this->intervalService->getSeconds($weekDay->getStartTime()->getValue()->format('H:i:s'));

                $end = $this->intervalService->getSeconds($this->intervalService->getEndTimeString($weekDay->getEndTime()->getValue()->format('H:i:s')));

                $intervals[$dayIndex]['free'][$start] = [$start, $end, [$providerLocationId]];
            }

            $intervals[$dayIndex]['free'] = $this->intervalService->getAvailableIntervals(
                $intervals[$dayIndex]['free'],
                isset($intervals[$dayIndex]['busy']) ? $intervals[$dayIndex]['busy'] : []
            );
        }

        return $intervals;
    }
}
