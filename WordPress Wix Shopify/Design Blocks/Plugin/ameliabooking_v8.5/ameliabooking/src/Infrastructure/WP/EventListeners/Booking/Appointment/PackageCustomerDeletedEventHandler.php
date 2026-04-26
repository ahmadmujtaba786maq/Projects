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
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class PackageCustomerDeletedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class PackageCustomerDeletedEventHandler
{
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
        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $container->get('application.emailNotification.service');

        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $container->get('application.smsNotification.service');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $container->get('application.whatsAppNotification.service');

        /** @var SettingsService $settingsService */
        $settingsService = $container->get('domain.settings.service');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $container->get('domain.bookable.package.repository');

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $container->get('domain.users.customers.repository');

        /** @var Package $package */
        $package = $packageRepository->getById($commandResult->getData()['packageCustomer']['packageId']);

        /** @var AbstractUser $customer */
        $customer = $customerRepository->getById($commandResult->getData()['packageCustomer']['customerId']);

        $packageReservation = array_merge(
            array_merge(
                $package->toArray(),
                [
                    'status'            => 'canceled',
                    'customer'          => $customer->toArray(),
                    'icsFiles'          => [],
                    'packageCustomerId' => $commandResult->getData()['packageCustomer']['packageId'],
                    'isRetry'           => null,
                    'recurring'         => array_merge(
                        $commandResult->getData()['appointments']['updatedAppointments'],
                        $commandResult->getData()['appointments']['deletedAppointments']
                    )
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
    }
}
