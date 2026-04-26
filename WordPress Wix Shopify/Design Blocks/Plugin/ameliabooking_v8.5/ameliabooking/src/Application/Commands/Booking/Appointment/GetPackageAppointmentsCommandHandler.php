<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesCustomersTable;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetPackageAppointmentsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetPackageAppointmentsCommandHandler extends CommandHandler
{
    /**
     * @param GetPackageAppointmentsCommand $command
     *
     * @return CommandResult
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(GetPackageAppointmentsCommand $command)
    {
        $result = new CommandResult();

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        /** @var AbstractPackageApplicationService $packageAS */
        $packageAS = $this->container->get('application.bookable.package');

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');

        $params = $command->getField('params');

        $itemsPerPageBackEnd = $settingsDS->getSetting('general', 'itemsPerPageBackEnd');

        if (!empty($params['dates'])) {
            !empty($params['dates'][0]) ? $params['dates'][0] .= ' 00:00:00' : null;
            !empty($params['dates'][1]) ? $params['dates'][1] .= ' 23:59:59' : null;
        }

        if (!empty($params['search'])) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully retrieved appointments');
            $result->setData(
                [
                    Entities::APPOINTMENTS     => [],
                    'availablePackageBookings' => [],
                    'occupied'                 => [],
                    'total'                    => 0,
                    'totalApproved'            => 0,
                    'totalPending'             => 0,
                ]
            );

            return $result;
        }

        $availablePackageBookings = [];

        $packageCustomerIds = [];

        $noResultsManagePackagesFilters = false;

        $totalPackagePurchases = 0;

        $customerId = isset($params['customerId']) ? $params['customerId'] : null;

        if (!empty($params['packageStatus']) || !empty($params['page']) || !empty($params['bookingsCount'])) {
            $packageCustomerIds = $packageCustomerRepository->getIds(
                [
                    'purchased'     => !empty($params['dates']) ? $params['dates'] : [],
                    'packages'      => !empty($params['packageId']) ? [$params['packageId']] : [],
                    'itemsPerPage'  => $itemsPerPageBackEnd,
                    'page'          => !empty($params['page']) ? $params['page'] : null,
                    'packageStatus' => !empty($params['packageStatus']) ? $params['packageStatus'] : null,
                    'customerId'    => $customerId,
                ]
            );

            $noResultsManagePackagesFilters = empty($packageCustomerIds);

            $totalPackagePurchases = $packageCustomerRepository->getPackagePurchasedCount(
                [
                    'packageCustomerIds' => !empty($packageCustomerIds) ? $packageCustomerIds : [],
                    'purchased'          => !empty($params['dates']) ? $params['dates'] : [],
                    'packages'           => !empty($params['packageId']) ? [$params['packageId']] : [],
                    'packageStatus'      => !empty($params['packageStatus']) ? $params['packageStatus'] : null,
                    'customerId'         => $customerId
                ]
            );
        }

        /** @var Collection $appointments */
        $appointments = new Collection();

        if (isset($params['customerId'])) {
            unset($params['customerId']);
        }

        $customersNoShowCountIds = [];

        $noShowTagEnabled = $settingsDS->getSetting('roles', 'enableNoShowTag');

        if (!$noResultsManagePackagesFilters) {
            $availablePackageBookings = $packageAS->getPackageAvailability(
                $appointments,
                [
                    'packageCustomerIds' => !empty($packageCustomerIds) ? $packageCustomerIds : [],
                    'purchased'          => !empty($params['dates']) ? $params['dates'] : [],
                    'customerId'         => $customerId,
                    'packageId'          => !empty($params['packageId']) ? (int)$params['packageId'] : null,
                    'managePackagePage'  => true
                ]
            );

            if ($noShowTagEnabled && !!$availablePackageBookings) {
                $customersNoShowCountIds[] = $availablePackageBookings[0]['customerId'];
            }
        }

        /** @var Collection $services */
        $services = $serviceRepository->getAllArrayIndexedById();

        $packageAS->setPackageBookingsForAppointments($appointments);

        $occupiedTimes = [];

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $groupedAppointments = [];

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var Service $service */
            $service = $services->getItem($appointment->getServiceId()->getValue());

            $bookingsCount = 0;

            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                // fix for wrongly saved JSON
                if (
                    $booking->getCustomFields() &&
                    json_decode($booking->getCustomFields()->getValue(), true) === null
                ) {
                    $booking->setCustomFields(null);
                }

                if ($bookingAS->isBookingApprovedOrPending($booking->getStatus()->getValue())) {
                    $bookingsCount++;
                }

                if ($noShowTagEnabled && !in_array($booking->getCustomerId()->getValue(), $customersNoShowCountIds)) {
                    $customersNoShowCountIds[] = $booking->getCustomerId()->getValue();
                }
            }

            $appointmentAS->calculateAndSetAppointmentEnd($appointment, $service);

            $minimumCancelTimeInSeconds = $settingsDS
                ->getEntitySettings($service->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToCanceling();

            $minimumCancelTime = DateTimeService::getCustomDateTimeObject(
                $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$minimumCancelTimeInSeconds} seconds");

            $date = $appointment->getBookingStart()->getValue()->format('Y-m-d');

            $cancelable = $currentDateTime <= $minimumCancelTime;

            $minimumRescheduleTimeInSeconds = $settingsDS
                ->getEntitySettings($service->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToRescheduling();

            $minimumRescheduleTime = DateTimeService::getCustomDateTimeObject(
                $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$minimumRescheduleTimeInSeconds} seconds");

            $reschedulable = $currentDateTime <= $minimumRescheduleTime;

            $groupedAppointments[$date]['date'] = $date;

            $groupedAppointments[$date]['appointments'][] = array_merge(
                $appointment->toArray(),
                [
                    'cancelable'    => $cancelable,
                    'reschedulable' => $reschedulable,
                    'past'          => $currentDateTime >= $appointment->getBookingStart()->getValue()
                ]
            );
        }

        $emptyBookedPackages = null;

        if (
            !empty($params['packageId']) &&
            empty($params['services']) &&
            empty($params['providers']) &&
            empty($params['locations']) &&
            !$noResultsManagePackagesFilters
        ) {
            /** @var AbstractPackageApplicationService $packageApplicationService */
            $packageApplicationService = $this->container->get('application.bookable.package');

            /** @var Collection $emptyBookedPackages */
            $emptyBookedPackages = $packageApplicationService->getEmptyPackages(
                [
                    'packageCustomerIds' => !empty($packageCustomerIds) ? $packageCustomerIds : [],
                    'packages'           => [$params['packageId']],
                    'purchased'          => !empty($params['dates']) ? $params['dates'] : [],
                    'customerId'         => $customerId
                ]
            );
        }

        $customersNoShowCount = [];

        if ($noShowTagEnabled && $customersNoShowCountIds) {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            $customersNoShowCount = $bookingRepository->countByNoShowStatus($customersNoShowCountIds);
        }


        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved appointments');
        $result->setData(
            [
                Entities::APPOINTMENTS     =>
                    !empty($params['asArray']) && filter_var($params['asArray'], FILTER_VALIDATE_BOOLEAN) ? $appointments->toArray() : $groupedAppointments,
                'availablePackageBookings' => $availablePackageBookings,
                'emptyPackageBookings'     => !empty($emptyBookedPackages) ? $emptyBookedPackages->toArray() : [],
                'occupied'                 => $occupiedTimes,
                'totalPackagePurchases'    => $totalPackagePurchases,
                'customersNoShowCount'     => $customersNoShowCount
            ]
        );

        return $result;
    }
}
