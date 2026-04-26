<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class PackageCustomerUpdatedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class PackageCustomerUpdatedEventHandler
{
    /** @var string */
    public const PACKAGE_CANCELED = 'packageCanceled';

    /**
     * @param CommandResult $commandResult
     * @param Container     $container
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public static function handle($commandResult, $container)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $container->get('domain.booking.appointment.repository');

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $container->get('domain.bookable.packageCustomerService.repository');

        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $container->get('application.emailNotification.service');

        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $container->get('application.smsNotification.service');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $container->get('application.whatsAppNotification.service');

        /** @var AbstractWebHookApplicationService $webHookService */
        $webHookService = $container->get('application.webHook.service');

        /** @var SettingsService $settingsService */
        $settingsService = $container->get('domain.settings.service');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $container->get('domain.bookable.package.repository');

        /** @var Collection $packageCustomerServices */
        $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
            [
                'packagesCustomers' => [$commandResult->getData()['packageCustomerId']]
            ]
        );

        if ($packageCustomerServices->length()) {
            /** @var PackageCustomerService $packageCustomerService */
            $packageCustomerService = $packageCustomerServices->getItem($packageCustomerServices->keys()[0]);

            /** @var CustomerRepository $customerRepository */
            $customerRepository = $container->get('domain.users.customers.repository');

            /** @var Customer $customer */
            $customer = $customerRepository->getById(
                $packageCustomerService->getPackageCustomer()->getCustomerId()->getValue()
            );

            /** @var Package $package */
            $package = $packageRepository->getById(
                $packageCustomerService->getPackageCustomer()->getPackageId()->getValue()
            );

            /** @var Collection $appointments */
            $appointments = $appointmentRepository->getFiltered(
                [
                    'packageCustomerId' => $commandResult->getData()['packageCustomerId']
                ]
            );

            $packageReservationData = [];

            /** @var Appointment $appointment */
            foreach ($appointments->getItems() as $appointment) {
                /** @var CustomerBooking $customerBooking */
                foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                    if (
                        $customerBooking->getPackageCustomerService() &&
                        in_array(
                            $customerBooking->getPackageCustomerService()->getId()->getValue(),
                            $packageCustomerServices->keys(),
                            false
                        )
                    ) {
                        $packageReservationData[] = [
                            'type'                     => Entities::APPOINTMENT,
                            Entities::APPOINTMENT      => $appointment->toArray(),
                            Entities::BOOKING          => $customerBooking->toArray(),
                            'appointmentStatusChanged' => false,
                        ];

                        break;
                    }
                }
            }

            $packageReservation = array_merge(
                array_merge(
                    $package->toArray(),
                    [
                        'status'            => $commandResult->getData()['status'] === 'approved' ?
                            'purchased' : $commandResult->getData()['status'],
                        'customer'          => $customer->toArray(),
                        'icsFiles'          => [],
                        'packageCustomerId' => $commandResult->getData()['packageCustomerId'],
                        'isRetry'           => null,
                        'recurring'         => $packageReservationData
                    ]
                )
            );

            $emailNotificationService->sendPackageNotifications($packageReservation, true);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendPackageNotifications($packageReservation, true);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendPackageNotifications($packageReservation, true);
            }

            $webHookService->process(self::PACKAGE_CANCELED, $packageReservation, null);
        }
    }
}
