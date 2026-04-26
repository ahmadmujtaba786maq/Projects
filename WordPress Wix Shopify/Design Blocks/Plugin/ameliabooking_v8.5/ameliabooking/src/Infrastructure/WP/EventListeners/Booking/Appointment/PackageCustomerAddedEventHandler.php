<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class PackageCustomerAddedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class PackageCustomerAddedEventHandler
{
    /** @var string */
    public const PACKAGE_PURCHASED = 'packagePurchased';

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
        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $container->get('domain.bookable.packageCustomerService.repository');

        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $container->get('application.emailNotification.service');

        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $container->get('application.smsNotification.service');

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

        /** @var SettingsService $settingsDS */
        $settingsDS = $container->get('domain.settings.service');

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $container->get('application.payment.service');


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

            $packageReservation = array_merge(
                array_merge(
                    $package->toArray(),
                    [
                        'status'            => 'purchased',
                        'customer'          => $customer->toArray(),
                        'icsFiles'          => [],
                        'packageCustomerId' => $commandResult->getData()['packageCustomerId'],
                        'isRetry'           => null,
                        'recurring'         => []
                    ]
                ),
                []
            );

            $paymentId = $commandResult->getData()['paymentId'];

            if (!empty($paymentId)) {
                $data = [
                    'type' => Entities::PACKAGE,
                    'package' => $package->toArray(),
                    'bookable' => $package->toArray(),
                    'packageReservations' => [],
                    'customer' => $customer->toArray(),
                    'paymentId' => $paymentId,
                    'packageCustomerId' => $commandResult->getData()['packageCustomerId'],
                    'booking' => null
                ];
                $packageReservation['paymentLinks'] = $paymentAS->createPaymentLink($data);
            }

            $packageReservation['onlyOneEmployee'] = $commandResult->getData()['onlyOneEmployee'];
            $emailNotificationService->sendPackageNotifications($packageReservation, true, $commandResult->getData()['notify']);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendPackageNotifications($packageReservation, true, $commandResult->getData()['notify']);
            }

            /** @var HelperService $helperService */
            $helperService = $container->get('application.helper.service');

            $packageReservation['customer']['customerPanelUrl'] = $helperService->getCustomerCabinetUrl(
                $packageReservation['customer']['email'],
                'email',
                null,
                null,
                ''
            );

            $webHookService->process(self::PACKAGE_PURCHASED, $packageReservation, null);
        }
    }
}
