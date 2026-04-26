<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Stats;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Stats\StatsService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetStatsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Stats
 */
class GetStatsCommandHandler extends CommandHandler
{
    /**
     * @param GetStatsCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function handle(GetStatsCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::DASHBOARD)) {
            throw new AccessDeniedException('You are not allowed to read coupons.');
        }

        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var StatsService $statsAS */
        $statsAS = $this->container->get('application.stats.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var AbstractPackageApplicationService $packageAS */
        $packageAS = $this->container->get('application.bookable.package');

        $startDate = $command->getField('params')['dates'][0] . ' 00:00:00';

        $endDate = $command->getField('params')['dates'][1] . ' 23:59:59';

        $previousPeriodStart = DateTimeService::getCustomDateTimeObject($startDate);

        $previousPeriodEnd = DateTimeService::getCustomDateTimeObject($endDate);

        $numberOfDays = $previousPeriodEnd->diff($previousPeriodStart)->days + 1;

        $serviceStatsParams = ['dates' => [$startDate, $endDate]];

        $customerStatsParams = ['dates' => [$startDate, $endDate]];

        $locationStatsParams = ['dates' => [$startDate, $endDate]];

        $employeeStatsParams = ['dates' => [$startDate, $endDate]];

        $appointmentStatsParams = ['dates' => [$startDate, $endDate], 'status' => BookingStatus::APPROVED];

        // Statistic
        $selectedPeriodStatistics = $statsAS->getRangeStatisticsData($appointmentStatsParams);

        $previousPeriodStatistics = $statsAS->getRangeStatisticsData(
            array_merge(
                $appointmentStatsParams,
                [
                    'dates' => [
                        $previousPeriodStart->modify("-{$numberOfDays} day")->format('Y-m-d H:i:s'),
                        $previousPeriodEnd->modify("-{$numberOfDays} day")->format('Y-m-d H:i:s'),
                    ]
                ]
            )
        );

        // Charts
        $customersStats = $statsAS->getCustomersStats($customerStatsParams);

        $employeesStats = $statsAS->getEmployeesStats($employeeStatsParams);

        $servicesStats = $statsAS->getServicesStats($serviceStatsParams);

        $locationsStats = $statsAS->getLocationsStats($locationStatsParams);

        /** @var Collection $periodAppointments */
        $periodAppointments = $appointmentRepo->getPeriodAppointments(
            [
                'dates' => [
                    DateTimeService::getNowDateTime(),
                ],
                'page' => 1
            ],
            10
        );

        /** @var Collection $upcomingAppointments */
        $upcomingAppointments = $periodAppointments->length() ? $appointmentRepo->getFiltered(
            array_merge(
                [
                    'ids'           => $periodAppointments->keys(),
                    'skipProviders' => true,
                ]
            )
        ) : new Collection();

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $upcomingAppointmentsArr = [];

        $todayApprovedAppointmentsCount = 0;

        $todayPendingAppointmentsCount = 0;

        $todayDateString = explode(' ', DateTimeService::getNowDateTime())[0];

        $packageAS->setPackageBookingsForAppointments($upcomingAppointments);

        $customersNoShowCount = [];

        $customersNoShowCountIds = [];

        $noShowTagEnabled = $settingsDS->getSetting('roles', 'enableNoShowTag');


        /** @var Appointment $appointment */
        foreach ($upcomingAppointments->getItems() as $appointment) {
            if ($appointment->getBookingStart()->getValue()->format('Y-m-d') === $todayDateString) {
                if ($appointment->getStatus()->getValue() === BookingStatus::APPROVED) {
                    $todayApprovedAppointmentsCount++;
                }

                if ($appointment->getStatus()->getValue() === BookingStatus::PENDING) {
                    $todayPendingAppointmentsCount++;
                }
            }

            $minimumCancelTimeInSeconds = $settingsDS
                ->getEntitySettings($appointment->getService()->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToCanceling();

            $minimumCancelTime = DateTimeService::getCustomDateTimeObject(
                $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$minimumCancelTimeInSeconds} seconds");

            $upcomingAppointmentsArr[] = array_merge(
                $appointment->toArray(),
                [
                    'cancelable' => $currentDateTime <= $minimumCancelTime,
                    'past'       => $currentDateTime >= $appointment->getBookingStart()->getValue()
                ]
            );

            foreach ($appointment->getBookings()->getItems() as $booking) {
                if ($noShowTagEnabled && !in_array($booking->getCustomerId()->getValue(), $customersNoShowCountIds)) {
                    $customersNoShowCountIds[] = $booking->getCustomerId()->getValue();
                }
            }
        }

        if ($noShowTagEnabled && $customersNoShowCountIds) {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            $customersNoShowCount = $bookingRepository->countByNoShowStatus($customersNoShowCountIds);
        }

        $selectedPeriodStatistics = apply_filters('amelia_get_stats_filter', $selectedPeriodStatistics);

        do_action('amelia_get_stats', $selectedPeriodStatistics);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved appointments.');
        $result->setData(
            [
                'count'                => [
                    'approved' => $todayApprovedAppointmentsCount,
                    'pending'  => $todayPendingAppointmentsCount,
                ],
                'selectedPeriodStats'  => $selectedPeriodStatistics,
                'previousPeriodStats'  => $previousPeriodStatistics,
                'employeesStats'       => $employeesStats,
                'servicesStats'        => $servicesStats,
                'locationsStats'       => $locationsStats,
                'customersStats'       => $customersStats,
                Entities::APPOINTMENTS => $upcomingAppointmentsArr,
                'appointmentsCount'    => 10,
                'customersNoShowCount' => $customersNoShowCount
            ]
        );

        return $result;
    }
}
