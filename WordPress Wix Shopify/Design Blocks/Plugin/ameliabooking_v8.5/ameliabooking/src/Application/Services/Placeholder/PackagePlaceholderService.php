<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Placeholder;

use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageFactory;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class PackagePlaceholderService
 *
 * @package AmeliaBooking\Application\Services\Placeholder
 */
class PackagePlaceholderService extends AppointmentPlaceholderService
{
    /**
     * @return array
     *
     * @throws ContainerException
     */
    public function getEntityPlaceholdersDummyData($type)
    {
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get("application.placeholder.appointment.service");

        $dateFormat = $settingsService->getSetting('wordpress', 'dateFormat');

        return array_merge(
            [
            'package_name'                => 'Package Name',
            'reservation_name'            => 'Package Name',
            'package_price'               => $helperService->getFormattedPrice(100),
            'package_deposit_payment'     => $helperService->getFormattedPrice(20),
            'package_description'         => 'Package Description',
            'package_duration'            => date_i18n($dateFormat, date_create()->getTimestamp()),
            'reservation_description'     => 'Reservation Description',
            'payment_due_amount'          => $helperService->getFormattedPrice(80)
            ],
            $placeholderService->getEntityPlaceholdersDummyData($type)
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array        $package
     * @param int          $bookingKey
     * @param string       $type
     * @param AbstractUser $customer
     * @param array        $allBookings
     * @param bool         $invoice
     * @param string       $notificationType
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function getPlaceholdersData(
        $package,
        $bookingKey = null,
        $type = null,
        $customer = null,
        $allBookings = null,
        $invoice = false,
        $notificationType = null
    ) {
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        if (!empty($customer) && empty($package['customer'])) {
            $package['customer'] = $customer->toArray();
        }

        $locale = !empty($package['isForCustomer']) && !empty($package['customer']['translations']) ?
            $helperService->getLocaleFromTranslations(
                $package['customer']['translations']
            ) : null;

        $paymentLinks = [
            'payment_link_woocommerce' => '',
            'payment_link_stripe' => '',
            'payment_link_paypal' => '',
            'payment_link_razorpay' => '',
            'payment_link_mollie' => '',
            'payment_link_square' => '',
        ];

        if (!empty($package['paymentLinks'])) {
            foreach ($package['paymentLinks'] as $paymentType => $paymentLink) {
                $paymentLinks[$paymentType] = $type === 'email' ? '<a href="' . $paymentLink . '">' . $paymentLink . '</a>' : $paymentLink;
            }
        }

        return array_merge(
            $paymentLinks,
            $this->getPackageData($package, $invoice),
            $this->getCompanyData($locale),
            $this->getCustomersData(
                $package,
                $type,
                0,
                UserFactory::create($package['customer'])
            ),
            $this->getRecurringAppointmentsData($package, $bookingKey, $type, 'package', null),
            [
                'icsFiles' => !empty($package['icsFiles']) ? $package['icsFiles'] : []
            ],
            $notificationType === 'customer_package_purchased' ? $this->getCouponsData($package, $type, 0) : []
        );
    }

    /**
     *
     * @param array  $reservationData
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function getInvoicePlaceholdersData($reservationData)
    {
        $reservationData['bookable']['packageCustomerId'] = $reservationData['packageCustomerId'];
        $reservationData['bookable']['customer']          = $reservationData['customer'];
        return $this->getPlaceholdersData($reservationData['bookable'], null, 'email', UserFactory::create($reservationData['customer']), null, true);
    }

    /**
     * @param array $package
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws Exception
     */
    private function getPackageData($package, $invoice = false)
    {
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $dateFormat = $settingsService->getSetting('wordpress', 'dateFormat');

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var Collection $packageCustomerServices */
        $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
            [
                'customerId' => $package['customer']['id'],
                'packages'   => [$package['id']]
            ]
        );

        $coupon = null;

        $endDate = null;

        $paymentType = '';

        $deposit = null;

        $wcItemTax = 0;

        /** @var PackageCustomer|null $packageCustomer */
        $packageCustomer = null;

        $invoiceItem = [];

        $price = $package['price'];

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
            /** @var PackageCustomerRepository $packageCustomerRepository */
            $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

            /** @var PackageCustomer $packageCustomer */
            $packageCustomer = $packageCustomerRepository->getById(
                $packageCustomerService->getPackageCustomer()->getId()->getValue()
            );

            if ($packageCustomerService->getPackageCustomer()->getEnd()) {
                if ($endDate === null) {
                    $endDate = $packageCustomerService->getPackageCustomer()->getEnd()->getValue();
                }

                if ($packageCustomerService->getPackageCustomer()->getEnd()->getValue() > $endDate) {
                    $endDate = $packageCustomerService->getPackageCustomer()->getEnd()->getValue();
                }
            }

            if ($packageCustomerService->getPackageCustomer()->getPayments()) {
                $payments = $packageCustomerService->getPackageCustomer()->getPayments()->getItems();

                $invoiceItem['invoice_paid_amount'] = 0;
                /** @var Payment $payment */
                foreach ($payments as $index => $payment) {
                    if (!empty($package['deposit'])) {
                        if ($payment->getStatus()->getValue() === PaymentStatus::PARTIALLY_PAID) {
                            $deposit   = $payment->getAmount()->getValue();
                            $wcItemTax = !empty($payment->getWcItemTaxValue()) ? $payment->getWcItemTaxValue()->getValue() : 0;
                        }

                        if (
                            !empty($package['packageCustomerId']) &&
                            $packageCustomerService->getPackageCustomer()->getId()->getValue() === $package['packageCustomerId'] &&
                            ($payment->getStatus()->getValue() === PaymentStatus::PARTIALLY_PAID || $payment->getStatus()->getValue() === PaymentStatus::PAID)
                        ) {
                            $invoiceItem['invoice_paid_amount'] += $payment->getAmount()->getValue();
                        }
                    }

                    switch ($payment->getGateway()->getName()->getValue()) {
                        case 'onSite':
                            $method = BackendStrings::getCommonStrings()['on_site'];
                            break;
                        case 'wc':
                            $method = BackendStrings::getSettingsStrings()['wc_name'];
                            break;
                        default:
                            $method = BackendStrings::getSettingsStrings()[$payment->getGateway()->getName()->getValue()];
                            break;
                    }

                    $paymentType .= ($index === array_keys($payments)[0] ? '' : ', ') . $method;

                    if (
                        !empty($package['packageCustomerId']) &&
                        $packageCustomerService->getPackageCustomer()->getId()->getValue() === $package['packageCustomerId']
                    ) {
                        $invoiceItem['invoice_number'] = $payment->getInvoiceNumber() ? $payment->getInvoiceNumber()->getValue() : '';
                        $invoiceItem['invoice_issued'] = !empty($payment->toArray()['created']) ? date_i18n($dateFormat, $payment->toArray()['created']) : '';
                        $invoiceItem['invoice_method'] = $payment->getGatewayTitle() ? $payment->getGatewayTitle()->getValue() : $method;
                    }
                }
            }

            if ($coupon === null && $packageCustomerService->getPackageCustomer()->getCouponId()) {
                /** @var CouponRepository $couponRepository */
                $couponRepository = $this->container->get('domain.coupon.repository');

                $couponId = $packageCustomer->getCouponId()->getValue();

                /** @var Coupon $coupon */
                $coupon = $couponRepository->getById($couponId);

                $packageCustomer->setCoupon($coupon);

                $coupon = $coupon->toArray();
            }
        }

        /** @var string $break */
        $break = '<p><br></p>';

        $couponsUsed = [];

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::PACKAGE);

        /** @var Package $bookable */
        $bookable = PackageFactory::create(
            [
                'price'           => $price,
                'calculatedPrice' => $package['calculatedPrice'],
                'discount'        => $package['discount'],
            ]
        );


        // get coupon for WC description
        if ($packageCustomer && $packageCustomer->getCouponId() && $packageCustomer->getCouponId()->getValue()) {
            /** @var CouponRepository $couponRepository */
            $couponRepository = $this->container->get('domain.coupon.repository');

            $coupon = $couponRepository->getById($packageCustomer->getCouponId()->getValue());

            $packageCustomer->setCoupon($coupon);

            $coupon = $coupon->toArray();
        }

        $amountData = $reservationService->getPaymentAmount($packageCustomer, $bookable, $invoice);

        $price = $amountData['price'];

        $discountValue = $amountData['discount'];

        $deductionValue = $amountData['deduction'];

        $expirationDate = null;

        $couponDiscount = $discountValue + $deductionValue;

        if ($coupon) {
            if (!empty($coupon['expirationDate'])) {
                $expirationDate = $coupon['expirationDate'];
            }

            $couponsUsed[] =
                $coupon['code'] . ' ' . $break .
                ($discountValue ? BackendStrings::getPaymentStrings()['discount_amount'] . ': ' .
                    $helperService->getFormattedPrice($discountValue) . ' ' . $break : '') .
                ($deductionValue ? BackendStrings::getPaymentStrings()['deduction'] . ': ' .
                    $helperService->getFormattedPrice($deductionValue) . ' ' . $break : '') .
                ($expirationDate ? BackendStrings::getPaymentStrings()['expiration_date'] . ': ' .
                    $expirationDate : '');
        }

        $invoiceItem['invoice_discount']     = $amountData['full_discount'];
        $invoiceItem['invoice_tax']          = $amountData['tax'];
        $invoiceItem['invoice_tax_rate']     = $amountData['tax_rate'];
        $invoiceItem['invoice_tax_excluded'] = $amountData['tax_excluded'];
        $invoiceItem['invoice_tax_type']     = $amountData['tax_type'];
        $invoiceItem['invoice_unit_price']   = $amountData['unit_price'];
        $invoiceItem['invoice_subtotal']     = $amountData['subtotal'];
        $invoiceItem['invoice_qty']          = 1;

        $locale = !empty($package['isForCustomer']) && !empty($package['customer']['translations']) ?
            $helperService->getLocaleFromTranslations(
                $package['customer']['translations']
            ) : null;

        $packageName = $helperService->getBookingTranslation(
            $locale,
            $package['translations'],
            'name'
        ) ?: $package['name'];

        $invoiceItem['item_name'] = $packageName;

        $packageDescription = $helperService->getBookingTranslation(
            $locale,
            $package['translations'],
            'description'
        ) ?: $package['description'];

        $timeZone = '';

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        if (
            $settingsService->getSetting('general', 'showClientTimeZone') &&
            !empty($package['isForCustomer'])
        ) {
            $timeZone = !empty($package['customer']['timeZone']) ? $package['customer']['timeZone'] : '';
        }

        $paymentDueAmount = $deposit !== null ?
            $helperService->getFormattedPrice($price - ($deposit - $wcItemTax)) :
            $helperService->getFormattedPrice((!empty($method) && $method === 'On-site') ? $price : 0);

        return [
            'reservation_name'        => $packageName,
            'package_name'            => $packageName,
            'package_description'     => $packageDescription,
            'package_duration'        => $endDate ?
                date_i18n($dateFormat, $endDate->getTimestamp()) :
                FrontendStrings::getBookingStrings()['package_book_unlimited'],
            'reservation_description' => $packageDescription,
            'package_price'           => $helperService->getFormattedPrice($price),
            'package_deposit_payment' => $deposit !== null ? $helperService->getFormattedPrice($deposit) : '',
            'payment_type'            => $paymentType,
            "payment_due_amount"      => $paymentDueAmount,
            'coupon_used'             => $couponsUsed ? implode($break, $couponsUsed) : '',
            'time_zone'               => $timeZone,
            'items'                   => [$invoiceItem],
            'invoice_number'          => isset($invoiceItem['invoice_number']) ? $invoiceItem['invoice_number'] : '',
            'invoice_issued'          => isset($invoiceItem['invoice_issued']) ? $invoiceItem['invoice_issued'] : '',
            'invoice_method'          => isset($invoiceItem['invoice_method']) ? $invoiceItem['invoice_method'] : '',
            'invoice_tax'             => isset($amountData['total_tax']) ? $amountData['total_tax'] : '',
        ];
    }

    /**
     * @param array $entity
     *
     * @param string $subject
     * @param string $body
     * @param int    $userId
     * @return array
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public function reParseContentForProvider($entity, $subject, $body, $userId)
    {
        $employeeSubject = $subject;

        $employeeBody = $body;

        foreach ($entity['recurring'] as $recurringData) {
            if ($recurringData['appointment']['providerId'] === $userId) {
                $employeeData = $this->getEmployeeData($recurringData['appointment']);

                $employeeSubject = $this->applyPlaceholders(
                    $subject,
                    $employeeData
                );

                $employeeBody = $this->applyPlaceholders(
                    $body,
                    $employeeData
                );
            }
        }
        if (empty($entity['recurring'])) {
            if (!empty($entity['onlyOneEmployee'])) {
                if ($entity['onlyOneEmployee']['id'] === $userId) {
                    $employeeData = $this->getEmployeeData(['providerId' => $entity['onlyOneEmployee']['id']]);

                    $employeeSubject = $this->applyPlaceholders(
                        $subject,
                        $employeeData
                    );

                    $employeeBody = $this->applyPlaceholders(
                        $body,
                        $employeeData
                    );
                }
            }

            /** @var \AmeliaBooking\Application\Services\Settings\SettingsService $settingsAS*/
            $settingsAS = $this->container->get('application.settings.service');

            $emptyPackageEmployees = $settingsAS->getEmptyPackageEmployees();
            if (!empty($emptyPackageEmployees)) {
                foreach ($emptyPackageEmployees as $employee) {
                    if ($employee['id'] === $userId) {
                        $employeeData = $this->getEmployeeData(['providerId' => $employee['id']]);

                        $employeeSubject = $this->applyPlaceholders(
                            $subject,
                            $employeeData
                        );

                        $employeeBody = $this->applyPlaceholders(
                            $body,
                            $employeeData
                        );
                    }
                }
            }
        }

        return [
            'body'    => $employeeBody,
            'subject' => $employeeSubject,
        ];
    }
}
