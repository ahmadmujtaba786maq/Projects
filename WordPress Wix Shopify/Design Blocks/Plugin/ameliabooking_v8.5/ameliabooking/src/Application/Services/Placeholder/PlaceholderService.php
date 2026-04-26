<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Placeholder;

use AmeliaBooking\Application\Services\Coupon\AbstractCouponApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponExpiredException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\LoginType;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use AmeliaBooking\Domain\ValueObjects\String\CustomFieldType;
use AmeliaBooking\Infrastructure\WP\Translations\LiteBackendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use DateTime;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class PlaceholderService
 *
 * @package AmeliaBooking\Application\Services\Placeholder
 */
abstract class PlaceholderService implements PlaceholderServiceInterface
{
    /** @var Container */
    protected $container;

    /**
     * ProviderApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $text
     * @param array  $data
     *
     * @return mixed
     */
    public function applyPlaceholders($text, $data)
    {
        unset($data['icsFiles']);

        unset($data['providersAppointments']);

        unset($data['invoice_items_booking']);
        unset($data['invoice_items_extras']);
        unset($data['invoice_items_event']);
        unset($data['items']);

        $placeholders = array_map(
            function ($placeholder) {
                return "%{$placeholder}%";
            },
            array_keys($data)
        );

        if ($text && strpos($text, '%amelia_dynamic_placeholder_') !== false) {
            $lastPos = 0;

            $dynamicPlaceholderStart = '%amelia_dynamic_placeholder_';

            while (($lastPos = strpos($text, $dynamicPlaceholderStart, $lastPos)) !== false) {
                $subText = substr($text, $lastPos + 1);

                $dynamicPlaceholder = substr($subText, 0, strpos($subText, '%'));

                $placeholders[] = '%' . $dynamicPlaceholder . '%';

                $data[$dynamicPlaceholder] =  apply_filters(
                    $dynamicPlaceholder,
                    $data
                );

                $lastPos = $lastPos + strlen($dynamicPlaceholderStart);
            }
        }

        return str_replace($placeholders, array_values($data), $text);
    }

    /**
     * @return array
     *
     * @throws ContainerException
     */
    public function getPlaceholdersDummyData($type)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var string $paragraphStart */
        $paragraphStart = $type === 'email' ? '<p>' : '';

        /** @var string $paragraphEnd */
        $paragraphEnd = $type === 'email' ? '</p>' : ($type === 'whatsapp' ? '; ' : PHP_EOL);

        $companySettings = $settingsService->getCategorySettings('company');

        $timezone = get_option('timezone_string');

        return array_merge(
            [
            'booked_customer'     =>
                $paragraphStart .
                BackendStrings::getNotificationsStrings()['ph_customer_full_name'] .
                ': John Micheal Doe ' .
                $paragraphEnd .
                $paragraphStart .
                BackendStrings::getNotificationsStrings()['ph_customer_phone'] .
                ': 193-951-2600 ' .
                $paragraphEnd .
                $paragraphStart .
                BackendStrings::getNotificationsStrings()['ph_customer_email'] .
                ': customer@domain.com ' .
                $paragraphEnd,
            'company_address'     => $companySettings['address'],
            'company_name'        => $companySettings['name'],
            'company_phone'       => $companySettings['phone'],
            'company_website'     => $companySettings['website'],
            'company_vat_number'  => $companySettings['vat'],
            'company_email'       => !empty($companySettings['email']) ? $companySettings['email'] : '',
            'customer_email'      => 'customer@domain.com',
            'customer_first_name' => 'John',
            'customer_last_name'  => 'Doe',
            'customer_full_name'  => 'John Doe',
            'customer_phone'      => '193-951-2600',
            'customer_note'       => 'Customer Note',
            'customer_panel_url'  => $this->container->get('domain.settings.service')->getSetting('roles', 'customerCabinet')['pageUrl'],
            'coupon_used'         => 'code123',
            'number_of_persons'   => 2,
            'time_zone'           => $timezone,
            'employee_email'      => 'employee@domain.com',
            'employee_first_name' => 'Richard',
            'employee_last_name'  => 'Roe',
            'employee_full_name'  => 'Richard Roe',
            'employee_phone'      => '150-698-1858',
            'employee_note'       => 'Employee Note',
            'employee_description' => 'Employee Description',
            'employee_panel_url'  => 'https://your_site.com/employee-panel',
            'location_address'        => $companySettings['address'] ? $companySettings['address'] : 'Address 123',
            'location_phone'          => $companySettings['phone'],
            'location_name'           => 'Location Name',
            'location_latitude'       => '40.748441',
            'location_longitude'      => '-73.987853',
            'location_description'    => 'Location Description',
            ],
            $this->getEntityPlaceholdersDummyData($type)
        );
    }

    /**
     * @param string|null $locale
     *
     * @return array
     */
    public function getCompanyData($locale = null)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        $companySettings = $settingsService->getCategorySettings('company');

        $companyName = $helperService->getBookingTranslation(
            $locale,
            json_encode($companySettings['translations']),
            'name'
        ) ?: $companySettings['name'];

        return [
            'company_address' => $companySettings['address'],
            'company_name'    => $companyName,
            'company_phone'   => $companySettings['phone'],
            'company_website' => $companySettings['website'],
            'company_vat_number' => $companySettings['vat'],
            'company_email'   => !empty($companySettings['email']) ? $companySettings['email'] : null,
            'company_logo'    => $companySettings['pictureThumbPath']
        ];
    }

    /**
     * @param array  $appointment
     * @param string $type
     * @param null   $bookingKey
     * @param null   $token
     *
     * @return array
     *
     * @throws ContainerException
     */
    protected function getBookingData($appointment, $type, $bookingKey = null, $token = null, $depositEnabled = null, $isGroup = null, $invoice = false)
    {
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        /** @var string $break */
        $break = $type === 'email' ? '<p><br></p>' : ($type === 'whatsapp' ? '; ' : PHP_EOL);

        $couponsUsed = [];

        $payment = null;

        $invoiceItem = [];

        $paymentLinks = [
            'payment_link_woocommerce' => '',
            'payment_link_stripe' => '',
            'payment_link_paypal' => '',
            'payment_link_razorpay' => '',
            'payment_link_mollie' => '',
            'payment_link_square' => ''
        ];

        $couponDiscount = 0;

        $amountData = [
            'price'     => 0,
            'discount'  => 0,
            'deduction' => 0,
        ];

        // If notification is for provider: Appointment price will be sum of all bookings prices
        // If notification is for customer: Appointment price will be price of his booking
        if ($bookingKey === null) {
            $numberOfPersonsData = [
                AbstractUser::USER_ROLE_PROVIDER => [
                    BookingStatus::APPROVED => 0,
                    BookingStatus::PENDING  => 0,
                    BookingStatus::CANCELED => 0,
                    BookingStatus::REJECTED => 0,
                    BookingStatus::NO_SHOW  => 0,
                    BookingStatus::WAITING  => 0,
                ]
            ];

            foreach ((array)$appointment['bookings'] as $customerBooking) {
                $amountData = $this->getAmountData($customerBooking, $appointment);

                $expirationDate = null;

                if (!empty($customerBooking['coupon']['expirationDate'])) {
                    $expirationDate = $customerBooking['coupon']['expirationDate'];
                }

                if (($amountData['discount'] || $amountData['deduction']) && !empty($customerBooking['info'])) {
                    $customerData = json_decode($customerBooking['info'], true);

                    if (!$customerData) {
                        $customerData = [
                            'firstName' => $customerBooking['customer']['firstName'],
                            'lastName'  => $customerBooking['customer']['lastName'],
                        ];
                    }

                    $couponsUsed[] =
                        BackendStrings::getCommonStrings()['customer'] . ': ' .
                        $customerData['firstName'] . ' ' . $customerData['lastName'] . ' ' . $break .
                        BackendStrings::getFinanceStrings()['code'] . ': ' .
                        $customerBooking['coupon']['code'] . ' ' . $break .
                        ($amountData['discount'] ? BackendStrings::getPaymentStrings()['discount_amount'] . ': ' .
                            $helperService->getFormattedPrice($amountData['discount']) . ' ' . $break : '') .
                        ($amountData['deduction'] ? BackendStrings::getPaymentStrings()['deduction'] . ': ' .
                            $helperService->getFormattedPrice($amountData['deduction']) . ' ' . $break : '') .
                        ($expirationDate ? BackendStrings::getPaymentStrings()['expiration_date'] . ': ' .
                            $expirationDate : '');
                }

                $numberOfPersonsData[AbstractUser::USER_ROLE_PROVIDER][$customerBooking['status']] +=
                    empty($customerBooking['ticketsData']) ? $customerBooking['persons'] : array_sum(array_column($customerBooking['ticketsData'], 'persons'));

                $payment = !empty($customerBooking['payments'][0]) ? $customerBooking['payments'][0] : null;
            }

            $numberOfPersons = [];

            foreach ($numberOfPersonsData[AbstractUser::USER_ROLE_PROVIDER] as $key => $value) {
                if ($value) {
                    $numberOfPersons[] = BackendStrings::getCommonStrings()[$key] . ': ' . $value;
                }
            }

            $numberOfPersons = implode($break, $numberOfPersons);

            $icsFiles = !empty($appointment['bookings'][0]['icsFiles']) ? $appointment['bookings'][0]['icsFiles'] : [];
        } else {
            $amountData = $this->getAmountData($appointment['bookings'][$bookingKey], $appointment, $invoice);

            $couponDiscount = $amountData['discount'] + $amountData['deduction'];

            $expirationDate = null;

            if (!empty($appointment['bookings'][$bookingKey]['coupon']['expirationDate'])) {
                $expirationDate = $appointment['bookings'][$bookingKey]['coupon']['expirationDate'];
            }

            if (!empty($appointment['bookings'][$bookingKey]['coupon']['code'])) {
                $couponsUsed[] =
                    $appointment['bookings'][$bookingKey]['coupon']['code'] . ' ' . $break .
                    ($amountData['discount'] ? BackendStrings::getPaymentStrings()['discount_amount'] . ': ' .
                        $helperService->getFormattedPrice($amountData['discount']) . ' ' . $break : '') .
                    ($amountData['deduction'] ? BackendStrings::getPaymentStrings()['deduction'] . ': ' .
                        $helperService->getFormattedPrice($amountData['deduction']) . ' ' . $break : '') .
                    ($expirationDate ? BackendStrings::getPaymentStrings()['expiration_date'] . ': ' .
                        $expirationDate : '');
            }

            $numberOfPersons =
                empty($appointment['bookings'][$bookingKey]['ticketsData']) ?
                $appointment['bookings'][$bookingKey]['persons'] :
                    array_sum(array_column($appointment['bookings'][$bookingKey]['ticketsData'], 'persons'));

            $invoiceItem['invoice_qty']          = $amountData['qty'];
            $invoiceItem['invoice_unit_price']   = $amountData['unit_price'];
            $invoiceItem['invoice_subtotal']     = $amountData['subtotal'];
            $invoiceItem['invoice_tax']          = $amountData['tax'];
            $invoiceItem['invoice_tax_rate']     = $amountData['tax_rate'];
            $invoiceItem['invoice_tax_excluded'] = $amountData['tax_excluded'];
            $invoiceItem['invoice_tax_type']     = $amountData['tax_type'];
            $invoiceItem['total_tax']            = $amountData['total_tax'];
            $invoiceItem['invoice_extras_tax']   = !empty($amountData['extras_tax']) ? $amountData['extras_tax'] : null;
            $invoiceItem['invoice_tickets_tax']  = !empty($amountData['tickets_tax']) ? $amountData['tickets_tax'] : null;

            $icsFiles = !empty($appointment['bookings'][$bookingKey]['icsFiles']) ? $appointment['bookings'][$bookingKey]['icsFiles'] : [];

            $payment = !empty($appointment['bookings'][$bookingKey]['payments'][0]) ? $appointment['bookings'][$bookingKey]['payments'][0] : null;

            $invoiceItem['invoice_paid_amount'] = 0;
            $invoiceItem['invoice_method']      = '';
            foreach (!empty($appointment['bookings'][$bookingKey]['payments']) ? $appointment['bookings'][$bookingKey]['payments'] : [] as $p) {
                if ($p['status'] === PaymentStatus::PARTIALLY_PAID || $p['status'] === PaymentStatus::PAID) {
                    $invoiceItem['invoice_paid_amount'] += $p['amount'];
                    $invoiceItem['invoice_method']       = $p['gateway'];
                }
            }

            $invoiceItem['invoice_discount'] = !empty($amountData['full_discount']) && $amountData['full_discount'] > 0 ? $amountData['full_discount'] : 0;

            if (!empty($payment['paymentLinks'])) {
                foreach ($payment['paymentLinks'] as $paymentType => $paymentLink) {
                    $paymentLinks[$paymentType] = $type === 'email' ? '<a href="' . $paymentLink . '">' . $paymentLink . '</a>' : $paymentLink;
                }
            }
        }

        $depositAmount = null;
        if (!empty($appointment['deposit']) || $depositEnabled) {
            $depositAmount = $payment ? $payment['amount'] : 0;
        }
        $paymentType = '';
        if ($payment) {
            switch ($payment['gateway']) {
                case 'onSite':
                    $paymentType = BackendStrings::getCommonStrings()['on_site'];
                    break;
                case 'wc':
                    $paymentType = BackendStrings::getSettingsStrings()['wc_name'];
                    break;
                case 'square':
                    $paymentType = BackendStrings::getSettingsStrings()['square'];
                    break;
                default:
                    $paymentType = BackendStrings::getSettingsStrings()[$payment['gateway']];
                    break;
            }
        }

        $appointmentPrice = $helperService->getFormattedPrice($amountData['price'] >= 0 ? $amountData['price'] : 0);

        $paymentDueAmount = $payment ?
            $helperService->getFormattedPrice(
                ($amountData['price'] >= 0 ? $amountData['price'] : 0) -
                ($payment['amount'] - (!empty($payment['wcItemTaxValue']) ? $payment['wcItemTaxValue'] : 0))
            ) : '';

        $bookingKeyForEmployee = null;

        if ($bookingKey === null || $isGroup) {
            $bookingKeyForEmployee = $isGroup ?
                $appointment['bookings'][$bookingKey]['id'] : $this->getBookingKeyForEmployee($appointment);
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $dateFormat = $settingsService->getSetting('wordpress', 'dateFormat');

        return array_merge(
            $paymentLinks,
            [
                "appointment_price" => $appointmentPrice,
                "booking_price"     => $appointmentPrice,
                "{$appointment['type']}_cancel_url" =>
                    $bookingKey !== null && isset($appointment['bookings'][$bookingKey]['id']) ?
                        AMELIA_ACTION_URL . '/bookings/cancel/' . $appointment['bookings'][$bookingKey]['id'] .
                        ($token ? '&token=' . $token : '') . "&type={$appointment['type']}" : '',
                'appointment_approve_url' =>
                    $bookingKeyForEmployee !== null ? (AMELIA_ACTION_URL . '/bookings/success/' . $bookingKeyForEmployee .
                        '&token=' . $token) : '',
                'appointment_reject_url' =>
                    $bookingKeyForEmployee !== null ? (AMELIA_ACTION_URL . '/bookings/reject/' . $bookingKeyForEmployee .
                        '&token=' . $token) : '',
                "{$appointment['type']}_deposit_payment"    => $depositAmount !== null ? $helperService->getFormattedPrice($depositAmount) : '',
                'payment_type'                      => $paymentType,
                'payment_status'                    => $payment ? $payment['status'] : '',
                'payment_gateway'                   => $payment ? $payment['gateway'] : '',
                'payment_created'                   => $payment && !empty($payment['created'])
                    ? date_i18n($dateFormat, strtotime($payment['created']))
                    : '',
                'payment_invoice_number'            => $payment ? $payment['invoiceNumber'] : '',
                'payment_gateway_title'             => $payment ? $payment['gatewayTitle'] : '',
                "payment_due_amount"                => $paymentDueAmount,
                'number_of_persons'                 => $numberOfPersons,
                'coupon_used'                       => $couponsUsed ? implode($break, $couponsUsed) : '',
                'icsFiles'                          => $icsFiles,
                'invoice_items_booking'             => [$invoiceItem]
            ]
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array $appointment
     * @param string $type
     * @param null $bookingKey
     * @param Customer $customerEntity
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws \Exception
     */
    public function getCustomersData($appointment, $type, $bookingKey = null, $customerEntity = null)
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var string $paragraphStart */
        $paragraphStart = $type === 'email' ? '<p>' : '';

        /** @var string $paragraphEnd */
        $paragraphEnd = $type === 'email' ? '</p>' : ($type === 'whatsapp' ? '; ' : PHP_EOL);

        // If the data is for employee
        if ($bookingKey === null) {
            $customers = [];
            $customerInformationData = [];

            $hasApprovedOrPendingStatus = in_array(
                BookingStatus::APPROVED,
                array_column($appointment['bookings'], 'status'),
                true
            ) ||
                in_array(
                    BookingStatus::PENDING,
                    array_column($appointment['bookings'], 'status'),
                    true
                );

            $bookedCustomerFullName = '';
            $bookedCustomerEmail    = '';
            $bookedCustomerPhone    = '';

            foreach ((array)$appointment['bookings'] as $customerBooking) {
                /** @var AbstractUser $customer */
                $customer = $userRepository->getById($customerBooking['customerId']);

                if (
                    (!$hasApprovedOrPendingStatus && $customerBooking['isChangedStatus']) ||
                    ($customerBooking['status'] !== BookingStatus::CANCELED && $customerBooking['status'] !== BookingStatus::REJECTED)
                ) {
                    if ($customerBooking['info']) {
                        $customerInformationData[] = json_decode($customerBooking['info'], true);
                    } else {
                        $customerInformationData[] = [
                            'firstName' => $customer->getFirstName()->getValue(),
                            'lastName'  => $customer->getLastName()->getValue(),
                            'phone'     => $customer->getPhone() ? $customer->getPhone()->getValue() : '',
                        ];
                    }

                    $customers[] = $customer;
                }

                if ($customerBooking['isChangedStatus']) {
                    $bookedCustomerFullName = $customer->getFullName();
                    $bookedCustomerEmail    = $customer->getEmail() ? $customer->getEmail()->getValue() : '';
                    $bookedCustomerPhone    = $customer->getPhone() ? $customer->getPhone()->getValue() : '';
                }
            }

            $phones = '';
            foreach ($customerInformationData as $key => $info) {
                if ($info['phone']) {
                    $phones .= $info['phone'] . ', ';
                } else {
                    $phones .= $customers[$key]->getPhone() ? $customers[$key]->getPhone()->getValue() . ', ' : '';
                }
            }

            $bookedCustomer =
                $paragraphStart . BackendStrings::getNotificationsStrings()['ph_customer_full_name'] . ': ' . $bookedCustomerFullName . $paragraphEnd;

            $bookedCustomer .=
                $bookedCustomerPhone ?
                    $paragraphStart . BackendStrings::getNotificationsStrings()['ph_customer_phone'] . ': ' . $bookedCustomerPhone . $paragraphEnd :
                    '';
            $bookedCustomer .=
                $bookedCustomerEmail ?
                    $paragraphStart . BackendStrings::getNotificationsStrings()['ph_customer_email'] . ': ' . $bookedCustomerEmail . $paragraphEnd :
                    '';

            return [
                'booked_customer'     => $paragraphStart ?
                    substr($bookedCustomer, 3, strlen($bookedCustomer) - 7) : $bookedCustomer,
                'customer_email'      => implode(
                    ', ',
                    array_map(
                        function ($customer) {
                            /** @var Customer $customer */
                            return $customer->getEmail()->getValue();
                        },
                        $customers
                    )
                ),
                'customer_first_name' => implode(
                    ', ',
                    array_map(
                        function ($info) {
                            return $info['firstName'];
                        },
                        $customerInformationData
                    )
                ),
                'customer_last_name'  => implode(
                    ', ',
                    array_map(
                        function ($info) {
                            return $info['lastName'];
                        },
                        $customerInformationData
                    )
                ),
                'customer_full_name'  => implode(
                    ', ',
                    array_map(
                        function ($info) {
                            return $info['firstName'] . ' ' . $info['lastName'];
                        },
                        $customerInformationData
                    )
                ),
                'customer_phone'      => substr($phones, 0, -2),
                'customer_phone_local' =>  str_replace('+', '', substr($phones, 0, -2)),
                'customer_note'       => implode(
                    ', ',
                    array_map(
                        function ($customer) {
                            /** @var Customer $customer */
                            return $customer->getNote() ? $customer->getNote()->getValue() : '';
                        },
                        $customers
                    )
                )
            ];
        }

        // If data is for customer
        /** @var Customer $customer */
        $customer = $customerEntity ?: (
            !empty($appointment['bookings'][$bookingKey]['customer'])
                ? UserFactory::create($appointment['bookings'][$bookingKey]['customer'])
                : $userRepository->getById($appointment['bookings'][$bookingKey]['customerId'])
        );

        $info = !empty($appointment['bookings'][$bookingKey]['info']) ?
            json_decode($appointment['bookings'][$bookingKey]['info']) : null;

        if ($info && $info->phone) {
            $phone = $info->phone;
        } else {
            $phone = $customer->getPhone() ? $customer->getPhone()->getValue() : '';
        }

        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        return [
            'customer_email'      => $customer->getEmail() ? $customer->getEmail()->getValue() : '',
            'customer_first_name' => $info ? $info->firstName : $customer->getFirstName()->getValue(),
            'customer_last_name'  => $info ? $info->lastName : $customer->getLastName()->getValue(),
            'customer_full_name'  => $info ? $info->firstName . ' ' . $info->lastName : $customer->getFullName(),
            'customer_phone'      => $phone,
            'customer_phone_local' => !empty($phone) ? str_replace('+', '', $phone) : '',
            'customer_note'       => $customer->getNote() ? $customer->getNote()->getValue() : '',
            'customer_panel_url'  => $helperService->getCustomerCabinetUrl(
                $customer->getEmail()->getValue(),
                $type,
                !empty($appointment['bookingStart']) ? explode(' ', $appointment['bookingStart'])[0] : null,
                !empty($appointment['bookingEnd']) ? explode(' ', $appointment['bookingEnd'])[0] : null,
                $info && property_exists($info, 'locale') ? $info->locale : ''
            )
        ];
    }

    /**
     * @param array $appointment
     * @param string $type
     * @param null $bookingKey
     *
     * @return array
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getCustomFieldsData($appointment, $type, $bookingKey = null)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $dateFormat = $settingsService->getSetting('wordpress', 'dateFormat');

        $customFieldsData = [];

        $bookingCustomFieldsKeys = [];

        if ($bookingKey === null) {
            $sendAllCustomFields =
                $settingsService->getSetting('notifications', 'sendAllCF') ||
                (array_key_exists('sendCF', $appointment) && $appointment['sendCF']);
            foreach ($appointment['bookings'] as $booking) {
                if (
                    (!$booking['isChangedStatus'] || (array_key_exists('isLastBooking', $booking) && !$booking['isLastBooking']))
                    && !(isset($appointment['isRescheduled']) ? $appointment['isRescheduled'] : false) && !$sendAllCustomFields
                ) {
                    continue;
                }

                if (
                    sizeof($appointment['bookings']) > 1 &&
                    ($booking['status'] === BookingStatus::CANCELED || $booking['status'] === BookingStatus::REJECTED)
                ) {
                    continue;
                }

                $bookingCustomFields = !empty($booking['customFields']) ? json_decode($booking['customFields'], true) : null;

                if ($booking['customerId'] && !isset($booking['customer'])) {
                    /** @var UserRepository $userRepository */
                    $userRepository = $this->container->get('domain.users.repository');

                    $booking['customer'] = $userRepository->getById($booking['customerId'])->toArray();
                }

                $customerCustomFields = !empty($booking['customer']['customFields']) ? json_decode($booking['customer']['customFields'], true) : null;

                if ($customerCustomFields) {
                    $bookingCustomFields =  $bookingCustomFields ? ($bookingCustomFields + $customerCustomFields) : $customerCustomFields;
                }


                if ($bookingCustomFields) {
                    foreach ($bookingCustomFields as $bookingCustomFieldKey => $bookingCustomField) {
                        if (!empty($bookingCustomField['value']) && !empty($bookingCustomField['type'])) {
                            if ($bookingCustomField['type'] === 'datepicker') {
                                $date = DateTime::createFromFormat('Y-m-d', $bookingCustomField['value']);
                                $bookingCustomField['value'] = date_i18n($dateFormat, $date->getTimestamp());
                            }

                            if (
                                $bookingCustomField['type'] === 'file' &&
                                (!empty($appointment['provider']) || !empty($appointment['providers']))
                            ) {
                                /** @var HelperService $helperService */
                                $helperService = $this->container->get('application.helper.service');

                                /** @var array $jwtSettings */
                                $jwtSettings = $settingsService->getSetting('roles', 'urlAttachment');

                                $provider_email = !empty($appointment['provider']) ?
                                    $appointment['provider']['email'] : $appointment['providers'][0]['email'];

                                $token = $helperService->getGeneratedJWT(
                                    $provider_email,
                                    $jwtSettings['headerJwtSecret'],
                                    DateTimeService::getNowDateTimeObject()->getTimestamp() + $jwtSettings['tokenValidTime'],
                                    LoginType::AMELIA_URL_TOKEN
                                );

                                $files = '';

                                if ($bookingCustomField['value']) {
                                    $entityId = $booking['id'];

                                    if ($customerCustomFields && array_key_exists($bookingCustomFieldKey, $customerCustomFields)) {
                                        $entityId = $booking['customerId'];
                                    }

                                    foreach ($bookingCustomField['value'] as $index => $file) {
                                        $files .= '<a href="'
                                            . AMELIA_ACTION_URL . '/fields/' . $bookingCustomFieldKey . '/' . $entityId . '/' . $index . '&token=' . $token
                                            . '">' . $file['name'] . '</a>';
                                    }

                                    $bookingCustomField['value'] = $files;
                                }
                            }

                            if (
                                $bookingCustomField['type'] === 'file' &&
                                (empty($appointment['provider']) && empty($appointment['providers']))
                            ) {
                                continue;
                            }

                            if (array_key_exists('custom_field_' . $bookingCustomFieldKey, $customFieldsData)) {
                                $value = $bookingCustomField['type'] === CustomFieldType::ADDRESS ? (
                                    $type === 'email' ?
                                        '<a href="https://maps.google.com/?q=' .
                                        $bookingCustomField['value'] . '" target="_blank">' .  $bookingCustomField['value'] .
                                        '</a>' :
                                        'https://maps.google.com/?q=' . str_replace(' ', '+', $bookingCustomField['value'])
                                ) : $bookingCustomField['value'];
                                $customFieldsData['custom_field_' . $bookingCustomFieldKey]
                                    .= is_array($value)
                                    ? '; ' . implode('; ', $value) :
                                    '; ' . $value;
                            } else {
                                $value = $bookingCustomField['type'] === CustomFieldType::ADDRESS ? (
                                $type === 'email' ?
                                    '<a href="https://maps.google.com/?q=' .
                                    $bookingCustomField['value'] . '" target="_blank">' .  $bookingCustomField['value'] .
                                    '</a>' :
                                    'https://maps.google.com/?q=' . str_replace(' ', '+', $bookingCustomField['value'])
                                ) : $bookingCustomField['value'];
                                $customFieldsData['custom_field_' . $bookingCustomFieldKey] =
                                    is_array($value)
                                        ? implode('; ', $value) : $value;
                            }

                            $bookingCustomFieldsKeys[(int)$bookingCustomFieldKey] = true;
                        }
                    }
                }
            }
        } else {
            if ($appointment['bookings'][$bookingKey]['customFields']) {
                $bookingCustomFields = !is_array($appointment['bookings'][$bookingKey]['customFields']) ?
                    json_decode($appointment['bookings'][$bookingKey]['customFields'], true) : $appointment['bookings'][$bookingKey]['customFields'];
            } else {
                $bookingCustomFields = [];
            }

            if ($appointment['bookings'][$bookingKey]['customerId'] && !isset($appointment['bookings'][$bookingKey]['customer'])) {
                /** @var UserRepository $userRepository */
                $userRepository = $this->container->get('domain.users.repository');

                $appointment['bookings'][$bookingKey]['customer'] = $userRepository->getById($appointment['bookings'][$bookingKey]['customerId'])->toArray();
            }

            if (!empty($appointment['bookings'][$bookingKey]['customer']['customFields'])) {
                $customerCustomFields = !is_array($appointment['bookings'][$bookingKey]['customer']['customFields']) ?
                    json_decode($appointment['bookings'][$bookingKey]['customer']['customFields'], true) :
                    $appointment['bookings'][$bookingKey]['customer']['customFields'];

                $bookingCustomFields += $customerCustomFields;
            }

            if ($bookingCustomFields) {
                foreach ((array)$bookingCustomFields as $bookingCustomFieldKey => $bookingCustomField) {
                    $bookingCustomFieldsKeys[(int)$bookingCustomFieldKey] = true;

                    if (
                        is_array($bookingCustomField) &&
                        array_key_exists('type', $bookingCustomField) &&
                        $bookingCustomField['type'] === 'file'
                    ) {
                        continue;
                    }

                    if (
                        is_array($bookingCustomField) &&
                        array_key_exists('type', $bookingCustomField) &&
                        $bookingCustomField['type'] === 'datepicker' &&
                        $bookingCustomField['value']
                    ) {
                        $date = DateTime::createFromFormat('Y-m-d', $bookingCustomField['value']);
                        $bookingCustomField['value'] = date_i18n($dateFormat, $date->getTimestamp());
                    }

                    if (isset($bookingCustomField['value'])) {
                        $value = $bookingCustomField['type'] === CustomFieldType::ADDRESS ? (
                            $type === 'email' ?
                                '<a href="https://maps.google.com/?q=' .
                                $bookingCustomField['value'] . '" target="_blank">' . $bookingCustomField['value'] .
                                '</a>' :
                                'https://maps.google.com/?q=' . str_replace(' ', '+', $bookingCustomField['value'])
                        ) : $bookingCustomField['value'];
                        $customFieldsData['custom_field_' . $bookingCustomFieldKey] = is_array($value)
                            ? implode('; ', $value) : $value;
                    } else {
                        $customFieldsData['custom_field_' . $bookingCustomFieldKey] = '';
                    }
                }
            }
        }

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        /** @var Collection $customFields */
        $customFields = $customFieldRepository->getAll();

        /** @var CustomField $customField */
        foreach ($customFields->getItems() as $customField) {
            if (!array_key_exists($customField->getId()->getValue(), $bookingCustomFieldsKeys)) {
                $customFieldsData['custom_field_' . $customField->getId()->getValue()] = '';
            }

            if ($customField->getType()->getValue() === 'content') {
                switch ($appointment['type']) {
                    case (Entities::APPOINTMENT):
                        /** @var Service $service */
                        foreach ($customField->getServices()->getItems() as $service) {
                            if ($service->getId()->getValue() === $appointment['serviceId']) {
                                $customFieldsData['custom_field_' . $customField->getId()->getValue()] =
                                    $customField->getLabel()->getValue();
                                break;
                            }
                        }

                        break;

                    case (Entities::EVENT):
                        /** @var Event $event */
                        foreach ($customField->getEvents()->getItems() as $event) {
                            if ($event->getId()->getValue() === $appointment['id']) {
                                $customFieldsData['custom_field_' . $customField->getId()->getValue()] =
                                    $customField->getLabel()->getValue();
                                break;
                            }
                        }

                        break;
                }
            }
        }

        return $customFieldsData;
    }

    /**
     * @param array  $appointment
     * @param string $type
     * @param null   $bookingKey
     *
     * @return array
     * @throws ContainerException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getCouponsData($appointment, $type, $bookingKey = null)
    {
        $couponsData = [];

        /** @var string $break */
        $break = $type === 'email' ? '<p><br></p>' : ($type === 'whatsapp' ? '; ' : PHP_EOL);

        if ($bookingKey !== null) {
            /** @var HelperService $helperService */
            $helperService = $this->container->get('application.helper.service');

            /** @var CouponRepository $couponRepository */
            $couponRepository = $this->container->get('domain.coupon.repository');

            /** @var AbstractCouponApplicationService $couponAS */
            $couponAS = $this->container->get('application.coupon.service');

            /** @var Collection $customerReservations */
            $customerReservations = new Collection();

            $type            = $appointment['type'];
            $customerId      = $type !== Entities::PACKAGE ? $appointment['bookings'][$bookingKey]['customerId'] : $appointment['customer']['id'];
            $couponsCriteria = [
                'notExpired'           => true,
                'notificationInterval' => true,
            ];

            if (!$customerId) {
                return $couponsData;
            }

            switch ($type) {
                case Entities::APPOINTMENT:
                    $couponsCriteria['entityIds'] = [$appointment['serviceId']];

                    $couponsCriteria['entityType'] = Entities::SERVICE;

                    break;

                case Entities::EVENT:
                    $couponsCriteria['entityIds'] = [$appointment['id']];

                    $couponsCriteria['entityType'] = Entities::EVENT;

                    break;

                case Entities::PACKAGE:
                    $couponsCriteria['entityIds'] = [$appointment['id']];

                    $couponsCriteria['entityType'] = Entities::PACKAGE;

                    break;
            }

            /** @var Collection $entityCoupons */
            $entityCoupons = $couponAS->getAllByCriteria($couponsCriteria);

            if (!$entityCoupons->length()) {
                return $couponsData;
            }

            switch ($type) {
                case Entities::APPOINTMENT:
                    /** @var AppointmentRepository $appointmentRepository */
                    $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

                    $customerReservations = $appointmentRepository->getPeriodAppointments(
                        [
                            'customerId'    => $customerId,
                            'skipServices'  => true,
                            'skipProviders' => true,
                            'skipCustomers' => true,
                            'skipPayments'  => true,
                            'skipExtras'    => true,
                            'skipCoupons'   => true,
                            'status'        => BookingStatus::APPROVED,
                            'bookingStatus' => BookingStatus::APPROVED,
                            'services'      => [
                                $appointment['serviceId']
                            ]
                        ]
                    );

                    break;

                case Entities::EVENT:
                    /** @var EventRepository $eventRepository */
                    $eventRepository = $this->container->get('domain.booking.event.repository');

                    /** @var Collection $eventsBookings */
                    $eventsBookings = $eventRepository->getBookingsByCriteria(
                        [
                            'ids'                   => [$appointment['id']],
                            'customerId'            => $customerId,
                            'customerBookingStatus' => BookingStatus::APPROVED,
                            'fetchBookings'         => false,
                            'fetchBookingsTickets'  => false,
                            'fetchBookingsUsers'    => false,
                            'fetchBookingsPayments' => false,
                        ]
                    );

                    /** @var Collection $customerReservations */
                    $customerReservations = new Collection();

                    /** @var Collection $eventBookings */
                    foreach ($eventsBookings->getItems() as $eventBookings) {
                        /** @var Collection $booking */
                        foreach ($eventBookings->getItems() as $bookingId => $booking) {
                            $customerReservations->addItem($booking, $bookingId);
                        }
                    }

                    break;

                case Entities::PACKAGE:
                    /** @var PackageCustomerRepository $packageCustomerRepository */
                    $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

                    $customerReservations = $packageCustomerRepository->getFiltered(
                        [
                            'packages'      => [$appointment['id']],
                            'customerId'    => $customerId,
                            'bookingStatus' => BookingStatus::APPROVED,
                        ]
                    );

                    break;
            }

            foreach (array_diff($couponRepository->getIds(), $entityCoupons->keys()) as $couponId) {
                $couponsData["coupon_{$couponId}"] = '';
            }

            /** @var Coupon $coupon */
            foreach ($entityCoupons->getItems() as $coupon) {
                $sendCoupon = (
                        $customerReservations->length() &&
                        !$coupon->getNotificationRecurring()->getValue() &&
                        $customerReservations->length() === $coupon->getNotificationInterval()->getValue()
                    ) || (
                        $customerReservations->length() &&
                        $coupon->getNotificationRecurring()->getValue() &&
                        $customerReservations->length() % $coupon->getNotificationInterval()->getValue() === 0
                    );

                try {
                    if ($sendCoupon && $couponAS->inspectCoupon($coupon, $customerId, true)) {
                        $couponsData["coupon_{$coupon->getId()->getValue()}"] =
                            FrontendStrings::getCommonStrings()['coupon_send_text'] . ' ' .
                            $coupon->getCode()->getValue() . ' ' . $break .
                            ($coupon->getDeduction() && $coupon->getDeduction()->getValue() ?
                                BackendStrings::getFinanceStrings()['deduction'] . ' ' .
                                $helperService->getFormattedPrice($coupon->getDeduction()->getValue()) . $break
                                : ''
                            ) .
                            ($coupon->getDiscount() && $coupon->getDiscount()->getValue() ?
                                BackendStrings::getPaymentStrings()['discount_amount'] . ' ' .
                                $coupon->getDiscount()->getValue() . '% ' . $break
                                : '') .
                            ($coupon->getExpirationDate() && $coupon->getExpirationDate()->getValue() ?
                                BackendStrings::getPaymentStrings()['expiration_date'] . ': ' .
                                date_i18n($coupon->getExpirationDate()->getValue()->format('Y-m-d')) : '');
                    } else {
                        $couponsData["coupon_{$coupon->getId()->getValue()}"] = '';
                    }
                } catch (CouponUnknownException $e) {
                    $couponsData["coupon_{$coupon->getId()->getValue()}"] = '';
                } catch (CouponInvalidException $e) {
                    $couponsData["coupon_{$coupon->getId()->getValue()}"] = '';
                } catch (CouponExpiredException $e) {
                    $couponsData["coupon_{$coupon->getId()->getValue()}"] = '';
                }
            }
        }

        return $couponsData;
    }

    /**
     * @param array $entity
     *
     * @param string $subject
     * @param string $body
     * @param int    $userId
     * @return array
     */
    public function reParseContentForProvider($entity, $subject, $body, $userId)
    {
        $employeeSubject = $subject;

        $employeeBody = $body;

        return [
            'body'    => $employeeBody,
            'subject' => $employeeSubject,
        ];
    }

    /**
     * @param array    $appointment
     * @param int|null $bookingKey
     *
     * @return string
     */
    protected function getLocale($appointment, $bookingKey)
    {
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        if (!empty($appointment['bookings'][$bookingKey]['info'])) {
            return $helperService->getLocaleFromBooking(
                $appointment['bookings'][$bookingKey]['info']
            );
        } elseif (!empty($appointment['bookings'][$bookingKey]['customer']['translations'])) {
            return $helperService->getLocaleFromTranslations(
                $appointment['bookings'][$bookingKey]['customer']['translations']
            );
        }

        return null;
    }

    /**
     * @param array    $reservation
     * @param int|null $bookingKey
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    protected function setData(&$reservation, $bookingKey = null)
    {
        $info = !empty($reservation['bookings'][$bookingKey]['info']) ?
            json_decode($reservation['bookings'][$bookingKey]['info'], true) : null;

        if (
            $bookingKey !== null &&
            (
                !empty($reservation['bookings'][$bookingKey]['customerId']) ||
                !empty($reservation['bookings'][$bookingKey]['customer']['id'])
            ) &&
            (
                ($info && empty($info['locale'])) ||
                (
                    !$info &&
                    !empty($reservation['bookings'][$bookingKey]['customer']) &&
                    empty($reservation['bookings'][$bookingKey]['customer']['translations'])
                )
            )
        ) {
            /** @var UserRepository $userRepository */
            $userRepository = $this->container->get('domain.users.repository');

            /** @var AbstractUser $customer */
            $customer = $userRepository->getById(
                !empty($reservation['bookings'][$bookingKey]['customerId']) ?
                    $reservation['bookings'][$bookingKey]['customerId'] :
                    $reservation['bookings'][$bookingKey]['customer']['id']
            );

            if ($customer->getTranslations()) {
                if ($info) {
                    $translations = json_decode($customer->getTranslations()->getValue(), true);

                    if ($translations && !empty($translations['defaultLanguage'])) {
                        $info['locale'] = $translations['defaultLanguage'];

                        $reservation['bookings'][$bookingKey]['info'] = json_encode($info);
                    }
                } else {
                    $reservation['bookings'][$bookingKey]['customer']['translations'] =
                        $customer->getTranslations()->getValue();
                }
            }
        }
    }

    /**
     * @param array $appointment
     *
     * @return int
     */
    protected function getBookingKeyForEmployee($appointment)
    {
        foreach ($appointment['bookings'] as $booking) {
            if ($booking['isLastBooking'] || $booking['isChangedStatus']) {
                return $booking['id'];
            }
        }

        if (!empty($appointment['isRescheduled']) && $appointment['isRescheduled']) {
            return $appointment['bookings'][0]['id'];
        }

        return null;
    }
}
