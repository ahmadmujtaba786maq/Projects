<?php

namespace AmeliaBooking\Domain\Services\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Schedule\DayOff;
use AmeliaBooking\Domain\Entity\Schedule\Period;
use AmeliaBooking\Domain\Entity\Schedule\PeriodLocation;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriod;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriodLocation;
use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Schedule\PeriodFactory;
use AmeliaBooking\Domain\Factory\Schedule\PeriodLocationFactory;
use AmeliaBooking\Domain\Factory\Schedule\WeekDayFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Interval\IntervalService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;
use AmeliaBooking\Infrastructure\Licence;

/**
 * Class ProviderService
 *
 * @package AmeliaBooking\Domain\Services\User
 */
class ProviderService
{
    /** @var IntervalService */
    private $intervalService;

    /**
     * ProviderService constructor.
     *
     * @param IntervalService $intervalService
     */
    public function __construct(
        $intervalService
    ) {
        $this->intervalService = $intervalService;
    }

    /**
     * @param WeekDay|SpecialDay $day
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function makePeriodsAvailable($day, $removeBreaks)
    {
        $locations = [];

        if ($removeBreaks) {
            $day->setTimeOutList(new Collection());
        }

        /** @var Period $period */
        foreach ($day->getPeriodList()->getItems() as $period) {
            if ($period->getLocationId() && empty($locations[$period->getLocationId()->getValue()])) {
                $locations[$period->getLocationId()->getValue()] = PeriodLocationFactory::create(
                    ['locationId' => $period->getLocationId()->getValue()]
                );
            }

            /** @var PeriodLocation $periodLocation */
            foreach ($period->getPeriodLocationList()->getItems() as $periodLocation) {
                if (empty($locations[$periodLocation->getLocationId()->getValue()])) {
                    $locations[$periodLocation->getLocationId()->getValue()] = PeriodLocationFactory::create(
                        ['locationId' => $periodLocation->getLocationId()->getValue()]
                    );
                }
            }
        }

        $day->setPeriodList(new Collection());

        $day->getPeriodList()->addItem(
            PeriodFactory::create(
                [
                    'startTime'          => '00:00:00',
                    'endTime'            => '24:00:00',
                    'locationId'         => sizeof($locations) === 1 ? array_keys($locations)[0] : null,
                    'periodServiceList'  => [],
                    'periodLocationList' => sizeof($locations) > 1 ? array_values($locations) : [],
                ]
            )
        );
    }

    /**
     * @param Collection $providers
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function setProvidersAlwaysAvailable($providers)
    {
        /** @var Provider $provider */
        foreach ($providers->getItems() as $k => $provider) {
            $providerWeekDayIndexes = [];

            /** @var WeekDay $weekDay */
            foreach ($provider->getWeekDayList()->getItems() as $weekDay) {
                $providerWeekDayIndexes[] = $weekDay->getDayIndex()->getValue();
            }

            /** @var WeekDay $weekDay */
            foreach ($provider->getWeekDayList()->getItems() as $index => $weekDay) {
                $weekDay->getStartTime()->getValue()->setTime(0, 0);
                $weekDay->getEndTime()->getValue()->modify('+1 day');
                $weekDay->getEndTime()->getValue()->setTime(0, 0);

                $this->makePeriodsAvailable($weekDay, true);
            }

            for ($i = 1; $i <= 7; $i++) {
                if (!in_array($i, $providerWeekDayIndexes)) {
                    $provider->getWeekDayList()->addItem(
                        WeekDayFactory::create(
                            [
                                'dayIndex'    => $i,
                                'startTime'   => '00:00:00',
                                'endTime'     => '00:00:00',
                                'timeOutList' => new Collection(),
                                'periodList'  => new Collection(
                                    [
                                        'startTime'         => '00:00:00',
                                        'endTime'           => '24:00:00',
                                        'periodServiceList' => [],
                                    ]
                                )
                            ]
                        )
                    );
                }
            }

            /** @var SpecialDay $specialDay */
            foreach ($provider->getSpecialDayList()->getItems() as $specialDay) {
                $this->makePeriodsAvailable($specialDay, false);
            }

            $provider->setDayOffList(new Collection());

            /** @var Collection $sortedWeekDays */
            $sortedWeekDays = new Collection();

            $allWeekDaysIndexes = [];

            /** @var WeekDay $weekDay */
            foreach ($provider->getWeekDayList()->getItems() as $key => $weekDay) {
                $allWeekDaysIndexes[$weekDay->getDayIndex()->getValue()] = $key;
            }

            $allWeekDaysKeysKeys = array_keys($allWeekDaysIndexes);

            sort($allWeekDaysKeysKeys);

            foreach ($allWeekDaysKeysKeys as $weekDayIndex) {
                $sortedWeekDays->addItem($provider->getWeekDayList()->getItem($allWeekDaysIndexes[$weekDayIndex]));
            }

            $provider->setWeekDayList($sortedWeekDays);
        }
    }

    /**
     *
     * @param Period|SpecialDayPeriod $period
     * @param Location                $providerLocation
     * @param Collection              $locations
     * @param bool                    $hasVisibleLocations
     *
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     */
    public function getProviderPeriodLocations($period, $providerLocation, $locations, $hasVisibleLocations)
    {
        /** @var Collection $availablePeriodLocations */
        $availablePeriodLocations = new Collection();

        if ($period->getPeriodLocationList()->length()) {
            /** @var PeriodLocation|SpecialDayPeriodLocation $periodLocation */
            foreach ($period->getPeriodLocationList()->getItems() as $periodLocation) {
                if (
                    $providerLocation &&
                    $periodLocation->getLocationId()->getValue() === $providerLocation->getId()->getValue() &&
                    ($hasVisibleLocations ? $providerLocation->getStatus()->getValue() === Status::VISIBLE : true)
                ) {
                    $availablePeriodLocations->addItem($providerLocation, $providerLocation->getId()->getValue());
                }
            }

            /** @var PeriodLocation|SpecialDayPeriodLocation $periodLocation */
            foreach ($period->getPeriodLocationList()->getItems() as $periodLocation) {
                /** @var Location $availableLocation */
                $availableLocation = $locations->keyExists($periodLocation->getLocationId()->getValue()) ?
                    $locations->getItem($periodLocation->getLocationId()->getValue()) : null;

                if (
                    $availableLocation &&
                    (
                    $providerLocation ?
                        $periodLocation->getLocationId()->getValue() !== $providerLocation->getId()->getValue() : true
                    ) &&
                    ($hasVisibleLocations ? $availableLocation->getStatus()->getValue() === Status::VISIBLE : true)
                ) {
                    $availablePeriodLocations->addItem($availableLocation, $availableLocation->getId()->getValue());
                }
            }
        } elseif ($period->getLocationId() && $period->getLocationId()->getValue()) {
            /** @var Location $availableLocation */
            $availableLocation = $locations->keyExists($period->getLocationId()->getValue()) ?
                $locations->getItem($period->getLocationId()->getValue()) : null;

            if (
                $availableLocation &&
                ($hasVisibleLocations ? $availableLocation->getStatus()->getValue() === Status::VISIBLE : true)
            ) {
                $availablePeriodLocations->addItem($availableLocation, $availableLocation->getId()->getValue());
            }
        } elseif (
            $providerLocation &&
            ($hasVisibleLocations ? $providerLocation->getStatus()->getValue() === Status::VISIBLE : true)
        ) {
            $availablePeriodLocations->addItem($providerLocation, $providerLocation->getId()->getValue());
        }

        return $availablePeriodLocations;
    }

    /**
     * @param Provider   $provider
     * @param Collection $services
     * @param bool       $allowHiddenServices
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function setProviderServices($provider, $services, $allowHiddenServices)
    {
        /** @var Collection $providerServiceList */
        $providerServiceList = new Collection();

        /** @var Service $providerService */
        foreach ($provider->getServiceList()->getItems() as $providerService) {
            if ($services->keyExists($providerService->getId()->getValue())) {
                /** @var Service $service */
                $service = $services->getItem($providerService->getId()->getValue());

                if ($allowHiddenServices ? true : $service->getStatus()->getValue() === Status::VISIBLE) {
                    $providerServiceList->addItem(
                        ServiceFactory::create(
                            array_merge(
                                $service->toArray(),
                                [
                                    'price'         => $providerService->getPrice()->getValue(),
                                    'minCapacity'   => $providerService->getMinCapacity()->getValue(),
                                    'maxCapacity'   => $providerService->getMaxCapacity()->getValue(),
                                    'customPricing' => $providerService->getCustomPricing() ?
                                        $providerService->getCustomPricing()->getValue() : null,
                                ]
                            )
                        ),
                        $service->getId()->getValue()
                    );
                }
            }
        }

        $provider->setServiceList($providerServiceList);
    }

    /**
     * Add appointments to provider's appointments list
     *
     * @param Collection $providers
     * @param Collection $appointments
     * @param bool       $isGloballyBusySlot
     *
     * @throws InvalidArgumentException
     */
    public function addAppointmentsToAppointmentList($providers, $appointments, $isGloballyBusySlot)
    {
        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            if ($isGloballyBusySlot) {
                /** @var Provider $provider */
                foreach ($providers->getItems() as $provider) {
                    /** @var Appointment $fakeAppointment */
                    $fakeAppointment = AppointmentFactory::create(
                        array_merge(
                            $appointment->toArray(),
                            ['providerId' => $provider->getId()->getValue()]
                        )
                    );

                    if (!$fakeAppointment->getService()->getTimeBefore()) {
                        $fakeAppointment->getService()->setTimeBefore(new Duration(0));
                    }

                    if (!$fakeAppointment->getService()->getTimeAfter()) {
                        $fakeAppointment->getService()->setTimeAfter(new Duration(0));
                    }

                    $provider->getAppointmentList()->addItem($fakeAppointment);
                }
            } elseif ($providers->keyExists($appointment->getProviderId()->getValue())) {
                /** @var Provider $provider */
                $provider = $providers->getItem($appointment->getProviderId()->getValue());

                $provider->getAppointmentList()->addItem($appointment);
            }
        }
    }

    /**
     * @param Provider $provider
     * @param array    $globalDaysOff
     * @param DateTime $startDateTime
     * @param DateTime $endDateTime
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function modifyProviderTimeZone($provider, $globalDaysOff, $startDateTime, $endDateTime)
    {
        /** @var Appointment $appointment */
        foreach ($provider->getAppointmentList()->getItems() as $appointment) {
            $appointment->getBookingStart()->getValue()->setTimezone(
                new DateTimeZone($provider->getTimeZone()->getValue())
            );

            $appointment->getBookingEnd()->getValue()->setTimezone(
                new DateTimeZone($provider->getTimeZone()->getValue())
            );
        }

        /** @var SpecialDay $specialDay */
        foreach ($provider->getSpecialDayList()->getItems() as $specialDay) {
            $specialDay->setStartDate(
                new DateTimeValue(
                    DateTimeService::getDateTimeObjectInTimeZone(
                        $specialDay->getStartDate()->getValue()->format('Y-m-d') . ' 00:00:00',
                        $provider->getTimeZone()->getValue()
                    )
                )
            );

            $specialDay->setEndDate(
                new DateTimeValue(
                    DateTimeService::getDateTimeObjectInTimeZone(
                        $specialDay->getEndDate()->getValue()->format('Y-m-d') . ' 00:00:00',
                        $provider->getTimeZone()->getValue()
                    )
                )
            );
        }

        /** @var DayOff $dayOff */
        foreach ($provider->getDayOffList()->getItems() as $dayOff) {
            $dayOff->setStartDate(
                new DateTimeValue(
                    DateTimeService::getDateTimeObjectInTimeZone(
                        $dayOff->getStartDate()->getValue()->format('Y-m-d') . ' 00:00:00',
                        $provider->getTimeZone()->getValue()
                    )
                )
            );

            $dayOff->setEndDate(
                new DateTimeValue(
                    DateTimeService::getDateTimeObjectInTimeZone(
                        $dayOff->getEndDate()->getValue()->format('Y-m-d') . ' 00:00:00',
                        $provider->getTimeZone()->getValue()
                    )
                )
            );
        }

        $yearsDiff = $startDateTime->diff($endDateTime)->format('%y');

        $yearsDiff = $yearsDiff === '0' ? '1' : $yearsDiff;

        $startYear = $startDateTime->format('Y');

        /** @var Collection $fakeAppointments */
        $fakeAppointments = new Collection();

        foreach ($globalDaysOff as $globalDayOff) {
            $dayOffParts = explode('-', $globalDayOff);

            if (sizeof($dayOffParts) === 2) {
                for ($i = 0; $i <= $yearsDiff; $i++) {
                    $dateOffStart = DateTimeService::getCustomDateTimeObject(
                        $startYear . '-' . $globalDayOff . ' 00:00'
                    )->modify("+$i years");

                    $dateOffEnd = DateTimeService::getCustomDateTimeObject(
                        $startYear . '-' . $globalDayOff . ' 00:00'
                    )->modify("+$i years")->modify('+1 days');

                    /** @var Appointment $fakeAppointment */
                    $fakeAppointment = AppointmentFactory::create(
                        [
                            'bookingStart'       => $dateOffStart->format('Y-m-d H:i'),
                            'bookingEnd'         => $dateOffEnd->format('Y-m-d H:i'),
                            'notifyParticipants' => false,
                            'serviceId'          => 0,
                            'providerId'         => $provider->getId()->getValue(),
                        ]
                    );

                    $fakeAppointment->getBookingStart()->getValue()->setTimezone(
                        new DateTimeZone($provider->getTimeZone()->getValue())
                    );

                    $fakeAppointment->getBookingEnd()->getValue()->setTimezone(
                        new DateTimeZone($provider->getTimeZone()->getValue())
                    );

                    $fakeAppointments->addItem($fakeAppointment);
                }
            } elseif (sizeof($dayOffParts) === 3) {
                /** @var Appointment $fakeAppointment */
                $fakeAppointment = AppointmentFactory::create(
                    [
                        'bookingStart'       => $globalDayOff . ' 00:00',
                        'bookingEnd'         => DateTimeService::getCustomDateTimeObject(
                            $globalDayOff . ' 00:00'
                        )->modify('+1 days')->format('Y-m-d H:i'),
                        'notifyParticipants' => false,
                        'serviceId'          => 0,
                        'providerId'         => $provider->getId()->getValue(),
                    ]
                );

                $fakeAppointment->getBookingStart()->getValue()->setTimezone(
                    new DateTimeZone($provider->getTimeZone()->getValue())
                );

                $fakeAppointment->getBookingEnd()->getValue()->setTimezone(
                    new DateTimeZone($provider->getTimeZone()->getValue())
                );

                $fakeAppointments->addItem($fakeAppointment);
            }
        }

        /** @var Appointment $fakeAppointment */
        foreach ($fakeAppointments->getItems() as $fakeAppointment) {
            $provider->getAppointmentList()->addItem($fakeAppointment);
        }
    }

    /**
     * @param Service $service
     * @param string  $timeZone
     *
     * @return array
     */
    public function getCustomPricing($service, $timeZone)
    {
        if ($service->getCustomPricing()) {
            $customPricing = json_decode($service->getCustomPricing()->getValue(), true);

            if (
                $customPricing &&
                $customPricing['enabled'] === 'period' &&
                Licence\Licence::$licence !== 'Lite' &&
                Licence\Licence::$licence !== 'Starter' &&
                Licence\Licence::$licence !== 'Basic'
            ) {
                foreach ($customPricing['periods']['default'] as &$item) {
                    $ranges = [];

                    foreach ($item['ranges'] as $range) {
                        $ranges[] = [
                            'from'  => $this->intervalService->getSeconds($range['from'] . ':00'),
                            'to'    => $this->intervalService->getSeconds($range['to'] . ':00'),
                            'price' => $range['price'],
                        ];
                    }

                    $item['ranges'] = $ranges;
                }

                foreach ($customPricing['periods']['custom'] as &$item) {
                    $days = new DatePeriod(
                        DateTimeService::getCustomDateTimeObjectInTimeZone(
                            $item['dates']['start'] . ' 00:00:00',
                            $timeZone
                        ),
                        new DateInterval('P1D'),
                        DateTimeService::getCustomDateTimeObjectInTimeZone(
                            $item['dates']['end'] . ' 23:59:59',
                            $timeZone
                        )
                    );

                    $dates = [];

                    /** @var DateTime $day */
                    foreach ($days as $day) {
                        $dates[$day->format('Y-m-d')] = true;
                    }

                    $ranges = [];

                    foreach ($item['ranges'] as $range) {
                        $ranges[] = [
                            'from'  => $this->intervalService->getSeconds($range['from'] . ':00'),
                            'to'    => $this->intervalService->getSeconds($range['to'] . ':00'),
                            'price' => $range['price'],
                        ];
                    }

                    $item['dates'] = $dates;

                    $item['ranges'] = $ranges;
                }

                return $customPricing;
            }
        }

        return [];
    }

    /**
     * @param array  $customPricing
     * @param string $dateString
     * @param string $timeInSeconds
     * @param string $timeZone
     *
     * @return float|null
     */
    public function getDateTimePrice($customPricing, $dateString, $timeInSeconds, $timeZone)
    {
        foreach ($customPricing['periods']['custom'] as $item) {
            if (array_key_exists($dateString, $item['dates'])) {
                foreach ($item['ranges'] as $range) {
                    if ($timeInSeconds >= $range['from'] && $timeInSeconds < $range['to']) {
                        return $range['price'];
                    }
                }
            }
        }

        $appStart = DateTimeService::getCustomDateTimeObjectInTimeZone($dateString, $timeZone);

        $dayIndex = (int)$appStart->format('N') - 1;

        foreach ($customPricing['periods']['default'] as $item) {
            if (in_array($dayIndex, $item['days'])) {
                foreach ($item['ranges'] as $range) {
                    if ($timeInSeconds >= $range['from'] && $timeInSeconds < $range['to']) {
                        return $range['price'];
                    }
                }
            }
        }

        return null;
    }
}
