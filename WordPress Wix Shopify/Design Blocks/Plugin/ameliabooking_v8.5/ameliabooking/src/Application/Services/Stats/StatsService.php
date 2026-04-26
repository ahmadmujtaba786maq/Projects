<?php

namespace AmeliaBooking\Application\Services\Stats;

use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Schedule\DayOff;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Schedule\PeriodFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class StatsService
 *
 * @package AmeliaBooking\Application\Services\Stats
 */
class StatsService
{
    private $container;

    /**
     * StatsService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $params
     *
     * @return array
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getCustomersStats($params)
    {
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var array $returningCustomers */
        $returningCustomers = array_column($bookingRepository->getReturningCustomers($params), 'customerId');

        /** @var array $bookings */
        $bookings = array_column($bookingRepository->getFilteredDistinctCustomersIds($params), 'customerId');

        // Calculate number of customers in past period.
        // E.g. If in a date filter is selected current week, calculate it for past week.
        $dateFrom = DateTimeService::getCustomDateTimeObject($params['dates'][0]);

        $dateTo = DateTimeService::getCustomDateTimeObject($params['dates'][1]);

        $diff = (int)$dateTo->diff($dateFrom)->format('%a') + 1;

        $dateFrom->modify('-' . $diff . 'days');
        $dateTo->modify('-' . $diff . 'days');

        $paramsPast = ['dates' => [$dateFrom->format('Y-m-d H:i:s'), $dateTo->format('Y-m-d H:i:s')]];

        $bookingsPast = array_column($bookingRepository->getFilteredDistinctCustomersIds($paramsPast), 'customerId');

        $pastPeriodCount = count($bookingsPast);

        $returningCount = count(array_intersect($returningCustomers, $bookings));

        $newCount = count($bookings) - $returningCount;

        return [
            'newCustomersCount'        => $newCount,
            'returningCustomersCount'  => $returningCount,
            'totalPastPeriodCustomers' => $pastPeriodCount
        ];
    }

    /**
     * @param array $params
     * @return array
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerException
     */
    public function getRangeStatisticsData($params)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $appointments */
        $appointments = $appointmentRepository->getFiltered(
            array_merge(
                $params,
                [
                    'skipServices'  => true,
                    'skipProviders' => true,
                    'skipCustomers' => true,
                    'skipPayments'  => true,
                    'skipExtras'    => true,
                    'skipCoupons'   => true,
                    'skipBookings'  => true,
                ]
            )
        );

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var Collection $bookings */
        $bookings = $appointments->length()
            ? $bookingRepository->getByCriteria(['appointmentIds' => $appointments->keys()])
            : new Collection();

        /** @var CustomerBooking $booking */
        foreach ($bookings->getItems() as $booking) {
            /** @var Appointment $appointment */
            $appointment = $appointments->getItem($booking->getAppointmentId()->getValue());

            $appointment->getBookings()->addItem($booking, $booking->getId()->getValue());
        }

        /** @var Collection $bookingsPayments */
        $bookingsPayments = new Collection();

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                $bookingsPayments->addItem(null, $booking->getId()->getValue());
            }
        }

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var Collection $payments */
        $payments = $bookingsPayments->length() ?
            $paymentRepository->getByCriteria(['bookingIds' => $bookingsPayments->keys()]) : new Collection();

        /** @var Payment $payment */
        foreach ($payments->getItems() as $payment) {
            if ($payment->getCustomerBookingId()) {
                $bookingsPayments->placeItem($payment, $payment->getCustomerBookingId()->getValue(), true);
            }
        }

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if (
                    $bookingsPayments->keyExists($booking->getId()->getValue()) &&
                    $bookingsPayments->getItem($booking->getId()->getValue())
                ) {
                    $booking->getPayments()->addItem($bookingsPayments->getItem($booking->getId()->getValue()));
                }
            }
        }

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ProviderService $providerService */
        $providerService = $this->container->get('domain.user.provider.service');

        /** @var Collection $services */
        $services = $serviceRepository->getAllArrayIndexedById();

        /** @var Collection $providers */
        $providers = $providerRepository->getWithSchedule(['dates' => $params['dates']]);

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $providerService->setProviderServices($provider, $services, true);
        }

        /** @var ProviderApplicationService $providerApplicationService */
        $providerApplicationService = $this->container->get('application.user.provider.service');

        /** @var AbstractPackageApplicationService $packageApplicationService */
        $packageApplicationService = $this->container->get('application.bookable.package');

        $packageDatesData = $packageApplicationService->getPackageStatsData($params);

        /** @var Collection $appointmentsPackageCustomerServices */
        $appointmentsPackageCustomerServices = $packageApplicationService->getPackageCustomerServicesForAppointments(
            $appointments
        );

        $stats = [];

        $statsPeriod = new \DatePeriod(
            DateTimeService::getCustomDateTimeObject($params['dates'][0]),
            new \DateInterval('P1D'),
            DateTimeService::getCustomDateTimeObject($params['dates'][1])
        );

        /** @var DateTime $date */
        foreach ($statsPeriod as $date) {
            $stats[$date->format('Y-m-d')] = null;
        }

        $weekDaysData = [];

        $specialDatesData = [];

        $providersDaysOff = [];

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $providerId = $provider->getId()->getValue();

            $providersDaysOff[$providerId] = [];

            /** @var DayOff $daysOff */
            foreach ($provider->getDayOffList()->getItems() as $daysOff) {
                $daysOffPeriod = new \DatePeriod(
                    $daysOff->getStartDate()->getValue(),
                    new \DateInterval('P1D'),
                    DateTimeService::getCustomDateTimeObject(
                        $daysOff->getEndDate()->getValue()->format('Y-m-d H:i:s')
                    )->modify('+1 days')
                );

                /** @var DateTime $date */
                foreach ($daysOffPeriod as $date) {
                    $providersDaysOff[$providerId][] = $date->format('Y-m-d');
                }
            }

            // get provider week day available time
            /** @var WeekDay $weekDay */
            foreach ($provider->getWeekDayList()->getItems() as $weekDay) {
                $dayIndex = $weekDay->getDayIndex()->getValue();

                if (!array_key_exists($dayIndex, $weekDaysData)) {
                    $weekDaysData[$dayIndex] = [];
                }

                if ($weekDay->getPeriodList()->length() === 0) {
                    $weekDay->getPeriodList()->addItem(
                        PeriodFactory::create(
                            [
                                'startTime'          => $weekDay->getStartTime()->getValue()->format('H:i:s'),
                                'endTime'            => $weekDay->getEndTime()->getValue()->format('H:i:s'),
                                'periodServiceList'  => [],
                                'periodLocationList' => [],
                            ]
                        )
                    );
                }

                $weekDaysData[$dayIndex][$providerId] = $providerApplicationService->getProviderScheduleIntervals(
                    $weekDay->getPeriodList(),
                    $weekDay->getTimeOutList()
                );
            }

            // get provider special day available time
            /** @var SpecialDay $specialDay */
            foreach ($provider->getSpecialDayList()->getItems() as $specialDay) {
                $specialDaysPeriod = new \DatePeriod(
                    $specialDay->getStartDate()->getValue(),
                    new \DateInterval('P1D'),
                    DateTimeService::getCustomDateTimeObject(
                        $specialDay->getEndDate()->getValue()->format('Y-m-d H:i:s')
                    )->modify('+1 days')
                );

                $specialDayExist = false;

                foreach ($specialDaysPeriod as $date) {
                    if (array_key_exists($date->format('Y-m-d'), $stats)) {
                        $specialDayExist = true;
                        continue;
                    }
                }

                if ($specialDayExist) {
                    $providerSpecialDaysIntervals = $providerApplicationService->getProviderScheduleIntervals(
                        $specialDay->getPeriodList(),
                        new Collection()
                    );

                    /** @var DateTime $date */
                    foreach ($specialDaysPeriod as $date) {
                        $dateString = $date->format('Y-m-d');

                        if (array_key_exists($dateString, $stats)) {
                            if (!array_key_exists($dateString, $specialDatesData)) {
                                $specialDatesData[$dateString] = [];
                            }

                            $specialDatesData[$dateString][$providerId] = $providerSpecialDaysIntervals;
                        }
                    }
                }
            }
        }

        $appointmentDatesData = [];



        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            $date = $appointment->getBookingStart()->getValue()->format('Y-m-d');

            $providerId = $appointment->getProviderId()->getValue();

            $serviceId = $appointment->getServiceId()->getValue();

            $appointmentDuration = $appointment->getBookingEnd()->getValue()->diff(
                $appointment->getBookingStart()->getValue()
            );

            if (!array_key_exists($date, $appointmentDatesData)) {
                $appointmentDatesData[$date] = [
                    'providers' => [],
                    'services' => []
                ];
            }

            if (!array_key_exists($providerId, $appointmentDatesData[$date]['providers'])) {
                $appointmentDatesData[$date]['providers'][$providerId] = [
                    'count' => 0,
                    'occupied' => 0,
                    'revenue' => 0
                ];
            }

            if (!array_key_exists($serviceId, $appointmentDatesData[$date]['services'])) {
                $appointmentDatesData[$date]['services'][$serviceId] = [
                    'count' => 0,
                    'occupied' => 0,
                    'revenue' => 0
                ];
            }

            $occupiedDuration = $appointmentDuration->h * 60 + $appointmentDuration->i;

            $appointmentDatesData[$date]['providers'][$providerId]['count']++;
            $appointmentDatesData[$date]['providers'][$providerId]['occupied'] += $occupiedDuration;

            $appointmentDatesData[$date]['services'][$serviceId]['count']++;
            $appointmentDatesData[$date]['services'][$serviceId]['occupied'] += $occupiedDuration;

            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if ($booking->getPackageCustomerService()) {
                    $packageApplicationService->updatePackageStatsData(
                        $packageDatesData,
                        $appointmentsPackageCustomerServices,
                        $booking->getPackageCustomerService()->getId()->getValue(),
                        $date,
                        $occupiedDuration
                    );
                } else {
                    /** @var Payment $payment */
                    foreach ($booking->getPayments()->getItems() as $payment) {
                        $appointmentDatesData[$date]['providers'][$providerId]['revenue'] +=
                            $payment->getAmount()->getValue();

                        $appointmentDatesData[$date]['services'][$serviceId]['revenue'] +=
                            $payment->getAmount()->getValue();
                    }
                }
            }
        }

        foreach ($stats as $dateKey => $dateStats) {
            $dayIndex = DateTimeService::getCustomDateTimeObject($dateKey)->format('N');

            // parse week day for provider
            if (array_key_exists($dayIndex, $weekDaysData)) {
                foreach ($weekDaysData[$dayIndex] as $providerKey => $weekDayData) {
                    if (!in_array($dateKey, $providersDaysOff[$providerKey], true)) {
                        $stats[$dateKey]['providers'][$providerKey] = [
                            'count' => 0,
                            'occupied' => 0,
                            'revenue' => 0,
                            'intervals' => $weekDayData
                        ];
                    }
                }
            }

            // parse special day for provider
            if (array_key_exists($dateKey, $specialDatesData)) {
                foreach ($specialDatesData[$dateKey] as $providerKey => $specialDayData) {
                    if (!in_array($dateKey, $providersDaysOff[$providerKey], true)) {
                        $stats[$dateKey]['providers'][$providerKey] = [
                            'count' => 0,
                            'occupied' => 0,
                            'revenue' => 0,
                            'intervals' => $specialDayData
                        ];
                    }
                }
            }

            if (array_key_exists($dateKey, $appointmentDatesData)) {
                foreach ($appointmentDatesData[$dateKey]['providers'] as $providerKey => $appointmentStatsData) {
                    if (
                        empty($stats[$dateKey]['providers']) ||
                        !array_key_exists($providerKey, $stats[$dateKey]['providers'])
                    ) {
                        $stats[$dateKey]['providers'][$providerKey] = [
                            'intervals' => []
                        ];
                    }

                    $stats[$dateKey]['providers'][$providerKey]['count'] = $appointmentStatsData['count'];

                    $stats[$dateKey]['providers'][$providerKey]['occupied'] = $appointmentStatsData['occupied'];

                    $stats[$dateKey]['providers'][$providerKey]['revenue'] = $appointmentStatsData['revenue'];
                }

                foreach ($appointmentDatesData[$dateKey]['services'] as $serviceKey => $appointmentStatsData) {
                    $stats[$dateKey]['services'][$serviceKey]['count'] = $appointmentStatsData['count'];

                    $stats[$dateKey]['services'][$serviceKey]['occupied'] = $appointmentStatsData['occupied'];

                    $stats[$dateKey]['services'][$serviceKey]['revenue'] = $appointmentStatsData['revenue'];
                }
            }

            if (array_key_exists($dateKey, $packageDatesData)) {
                foreach ($packageDatesData[$dateKey] as $packageKey => $packageStatsData) {
                    $stats[$dateKey]['packages'][$packageKey]['count'] = $packageStatsData['count'];

                    $stats[$dateKey]['packages'][$packageKey]['purchased'] = $packageStatsData['purchased'];

                    $stats[$dateKey]['packages'][$packageKey]['occupied'] = $packageStatsData['occupied'];

                    $stats[$dateKey]['packages'][$packageKey]['revenue'] = $packageStatsData['revenue'];

                    $stats[$dateKey]['packages'][$packageKey]['intervals'] = [];
                }
            }
        }

        return $stats;
    }

    /**
     * @param $params
     *
     * @return array
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getEmployeesStats($params)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $appointments = $providerRepository->getAllNumberOfAppointments($params);

        $views = $providerRepository->getAllNumberOfViews($params);

        return array_values(array_replace_recursive($appointments, $views));
    }

    /**
     * @param $providerId
     *
     * @return bool
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function addEmployeesViewsStats($providerId)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $providerRepository->beginTransaction();

        if (!$providerRepository->addViewStats($providerId)) {
            $providerRepository->rollback();

            return false;
        }

        return $providerRepository->commit();
    }

    /**
     * @param $params
     *
     * @return array
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getServicesStats($params)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $appointments = $serviceRepository->getAllNumberOfAppointments($params);

        $views = $serviceRepository->getAllNumberOfViews($params);

        return array_values(array_replace_recursive($appointments, $views));
    }

    /**
     * @param $serviceId
     *
     * @return bool
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function addServicesViewsStats($serviceId)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $serviceRepository->beginTransaction();

        if (!$serviceRepository->addViewStats($serviceId)) {
            $serviceRepository->rollback();

            return false;
        }

        return $serviceRepository->commit();
    }

    /**
     * @param $params
     *
     * @return array
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getLocationsStats($params)
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        $appointments = $locationRepository->getAllNumberOfAppointments($params);

        $views = $locationRepository->getAllNumberOfViews($params);

        return array_values(array_replace_recursive($appointments, $views));
    }

    /**
     * @param $locationId
     *
     * @return bool
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function addLocationsViewsStats($locationId)
    {
        if ($locationId) {
            /** @var LocationRepository $locationRepository */
            $locationRepository = $this->container->get('domain.locations.repository');
            $locationRepository->beginTransaction();
            if (!$locationRepository->addViewStats($locationId)) {
                $locationRepository->rollback();

                return false;
            }
            return $locationRepository->commit();
        }

        return false;
    }
}
