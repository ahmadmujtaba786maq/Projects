<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Report;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Report\ReportServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\WP\Translations\LiteBackendStrings;

/**
 * Class GetCustomersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Report
 */
class GetAppointmentsCommandHandler extends CommandHandler
{
    /**
     * @param GetAppointmentsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetAppointmentsCommand $command)
    {
        $currentUser = $this->getContainer()->get('logged.in.user');

        if (!$command->getPermissionService()->currentUserCanRead(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to read appointments.');
        }

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var ReportServiceInterface $reportService */
        $reportService = $this->container->get('infrastructure.report.csv.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');
        /** @var AbstractPackageApplicationService $packageAS */
        $packageAS = $this->container->get('application.bookable.package');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $params = $command->getField('params');

        if (!empty($params['dates']) && $params['dates'][0]) {
            $params['dates'][0] .= ' 00:00:00';
        }

        if (!empty($params['dates']) && !empty($params['dates'][1])) {
            $params['dates'][1] .= ' 23:59:59';
        }

        switch ($currentUser->getType()) {
            case 'customer':
                $params['customerId'] = $currentUser->getId()->getValue();
                break;
            case 'provider':
                $params['providers'] = [$currentUser->getId()->getValue()];
                break;
        }

        $appointments = $appointmentRepo->getFiltered(array_merge($params, ['withLocations' => true]));
        $packageAS->setPackageBookingsForAppointments($appointments);

        $appointmentsArray = isset($params['count']) ?
            array_slice($appointments->toArray(), 0, $params['count']) :
            $appointments->toArray();


        $rows = [];

        $dateFormat = $settingsDS->getSetting('wordpress', 'dateFormat');
        $timeFormat = $settingsDS->getSetting('wordpress', 'timeFormat');

        $customFields = [];

        $allCustomFields = $customFieldRepository->getAll();

        if (in_array('customFields', $params['fields'], true)) {
            foreach ((array)$appointmentsArray as $appointment) {
                foreach ((array)$appointment['bookings'] as $booking) {
                    $customFieldsJson = json_decode($booking['customFields'], true);
                    foreach ((array)$customFieldsJson as $cfId => $customFiled) {
                        if (!in_array($cfId, array_keys($customFields))) {
                            /** @var CustomField $item **/
                            $item = $allCustomFields->keyExists($cfId) ? $allCustomFields->getItem($cfId) : null;
                            if ($item) {
                                $customFields[$cfId] = $item->getLabel()->getValue();
                            }
                        }
                    }
                }
            }
        }

        $extras = [];

        if (in_array('extras', $params['fields'], true)) {
            foreach ((array)$appointmentsArray as $appointment) {
                $extraIds = [];

                foreach ((array)$appointment['bookings'] as $booking) {
                    foreach ((array)$booking['extras'] as $extra) {
                        if (!in_array($extra['extraId'], $extraIds) && !isset($extras[$extra['extraId']])) {
                            $extraIds[] = $extra['extraId'];
                        }
                    }
                }

                if (!empty($extraIds)) {
                    $items = $extraRepository->getByIds($extraIds);
                    foreach ($items->getItems() as $item) {
                        $extras[$item->getId()->getValue()] = $item->toArray();
                    }
                }
            }
        }

        foreach ((array)$appointmentsArray as $appointment) {
            $numberOfPersonsData = [
                AbstractUser::USER_ROLE_PROVIDER => [
                    BookingStatus::APPROVED => 0,
                    BookingStatus::PENDING  => 0,
                    BookingStatus::CANCELED => 0,
                    BookingStatus::REJECTED => 0,
                    BookingStatus::NO_SHOW => 0,
                ]
            ];

            foreach ((array)$appointment['bookings'] as $booking) {
                $numberOfPersonsData[AbstractUser::USER_ROLE_PROVIDER][$booking['status']] += $booking['persons'];
            }

            $numberOfPersons = [];

            foreach ((array)$numberOfPersonsData[AbstractUser::USER_ROLE_PROVIDER] as $key => $value) {
                if ($value) {
                    $numberOfPersons[] = BackendStrings::getCommonStrings()[$key] . ': ' . $value;
                }
            }

            $row = [];

            $customers = [];
            $rowCF     = [];
            $rowExtras = [];
            $extraInfo = [];

            if ($params['separate'] !== "true") {
                foreach ((array)$appointment['bookings'] as $booking) {
                    $infoJson = json_decode($booking['info'], true);

                    $customerInfo = $infoJson ?: $booking['customer'];

                    $phone = $booking['customer']['phone'] ?: '';

                    $customers[] =
                        $customerInfo['firstName'] . ' ' . $customerInfo['lastName'] . ' ' .
                        ($booking['customer']['email'] ?: '') . ' ' . ($customerInfo['phone'] ?: $phone);

                    $customFieldsJson = json_decode($booking['customFields'], true);
                    foreach ($customFields as $customFieldId => $customFieldLabel) {
                        $value = '';
                        foreach ((array)$customFieldsJson as $cfId => $customFiled) {
                            if ($cfId === $customFieldId) {
                                if ($customFiled['type'] === 'file') {
                                    $value = '';
                                    foreach ($customFiled['value'] as $cfIndex => $cfFile) {
                                        $value .=
                                            ($cfIndex === 0 ? '' : ' | ')  .
                                            (AMELIA_UPLOADS_FILES_PATH_USE ?
                                                AMELIA_ACTION_URL . '/fields/' . $customFieldId . '/' . $booking['id'] . '/' . $cfIndex :
                                                AMELIA_UPLOADS_FILES_URL . $booking['id'] . '_' . $customFiled['value'][$cfIndex]['name']);
                                    }
                                } elseif (is_array($customFiled['value'])) {
                                    $value = implode('|', $customFiled['value']);
                                } else {
                                    $value = $customFiled['value'];
                                }
                            }
                        }
                        if (!empty($rowCF[$customFieldLabel])) {
                            $rowCF[$customFieldLabel] .= ', ' . $value;
                        } else {
                            $rowCF[$customFieldLabel] = $value;
                        }
                    }

                    foreach ($booking['extras'] as $k => $extra) {
                        if ($k > 0) {
                            $extraInfo[$booking['id']] .=  ', ' . $extras[$extra['extraId']]['name'] . ' x ' . $extra['quantity'];
                        } else {
                            $extraInfo[$booking['id']] = $extras[$extra['extraId']]['name'] . ' x ' . $extra['quantity'];
                        }
                    }
                }

                if (in_array('extras', $params['fields'], true)) {
                    $rowExtras[LiteBackendStrings::getCommonStrings()['extras']] =  implode('|', $extraInfo);
                }

                if (in_array('customers', $params['fields'], true)) {
                    $row[BackendStrings::getCustomerStrings()['customers']] = implode(', ', $customers);
                }

                $this->getRowData($params, $row, $appointment, $dateFormat, $timeFormat, $numberOfPersons);

                $mergedRow =
                    apply_filters(
                        'amelia_before_csv_export_appointments',
                        array_merge($row, $rowCF, $rowExtras),
                        $appointment,
                        $params['separate'],
                        null
                    );

                $rows[] = $mergedRow;
            } else {
                foreach ((array)$appointment['bookings'] as $booking) {
                    $row[BackendStrings::getAppointmentStrings()['appointment_id']] = $appointment['id'];
                    if (in_array('customers', $params['fields'], true)) {
                        $infoJson = json_decode($booking['info'], true);

                        $customerInfo = $infoJson ?: $booking['customer'];

                        $phone = $booking['customer']['phone'] ?: '';

                        $row[BackendStrings::getAppointmentStrings()['customer_name']]  = $customerInfo['firstName'] . ' ' . $customerInfo['lastName'];
                        $row[BackendStrings::getAppointmentStrings()['customer_email']] = $booking['customer']['email'];
                        $row[BackendStrings::getAppointmentStrings()['customer_phone']] = $customerInfo['phone'] ? $customerInfo['phone'] : $phone;
                    }

                    $this->getRowData($params, $row, $appointment, $dateFormat, $timeFormat, $numberOfPersons, $booking);

                    $customFieldsJson = json_decode($booking['customFields'], true);
                    if (in_array('customFields', $params['fields'], true)) {
                        foreach ($customFields as $customFieldId => $customFieldLabel) {
                            $value = '';
                            foreach ((array)$customFieldsJson as $cfId => $customFiled) {
                                if ($cfId === $customFieldId) {
                                    if ($customFiled['type'] === 'file') {
                                        $value = '';
                                        foreach ($customFiled['value'] as $cfIndex => $cfFile) {
                                            $value .=
                                                ($cfIndex === 0 ? '' : ' | ')  .
                                                (AMELIA_UPLOADS_FILES_PATH_USE ?
                                                    AMELIA_ACTION_URL . '/fields/' . $customFieldId . '/' . $booking['id'] . '/' . $cfIndex :
                                                    AMELIA_UPLOADS_FILES_URL . $booking['id'] . '_' . $customFiled['value'][$cfIndex]['name']);
                                        }
                                    } elseif (is_array($customFiled['value'])) {
                                        $value = implode('|', $customFiled['value']);
                                    } else {
                                        $value = $customFiled['value'];
                                    }
                                }
                            }
                            $row[$customFieldLabel] = $value;
                        }
                    }

                    if (in_array('extras', $params['fields'], true)) {
                        $extraInfo = [];
                        foreach ($booking['extras'] as $extra) {
                            $extraInfo[] = $extras[$extra['extraId']]['name'] . ' x ' . $extra['quantity'];
                        }

                        $row[LiteBackendStrings::getCommonStrings()['extras']] =  implode(', ', $extraInfo);
                    }

                    $row = apply_filters('amelia_before_csv_export_appointments', $row, $appointment, $params['separate'], $booking);

                    $rows[] = $row;
                }
            }
        }

        $reportService->generateReport($rows, Entities::APPOINTMENT . 's', $params['delimiter']);

        $result->setAttachment(true);

        return $result;
    }

    /**
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function getRowData($params, &$row, $appointment, $dateFormat, $timeFormat, $numberOfPersons, $booking = null)
    {
        if (in_array('employee', $params['fields'], true)) {
            $row[BackendStrings::getCommonStrings()['employee']] =
                $appointment['provider']['firstName'] . ' ' . $appointment['provider']['lastName'];
        }

        if (in_array('service', $params['fields'], true)) {
            $row[BackendStrings::getCommonStrings()['service']] = $appointment['service']['name'];
        }

        if (in_array('location', $params['fields'], true)) {
            $row[BackendStrings::getCommonStrings()['location']] = !empty($appointment['location']) ?
                (!empty($appointment['location']['address']) ? $appointment['location']['address'] : $appointment['location']['name']) : '';
        }

        if (in_array('startTime', $params['fields'], true)) {
            $row[BackendStrings::getAppointmentStrings()['start_time']] =
                DateTimeService::getCustomDateTimeObject($appointment['bookingStart'])
                    ->format($dateFormat . ' ' . $timeFormat);
        }

        if (in_array('endTime', $params['fields'], true)) {
            $row[BackendStrings::getAppointmentStrings()['end_time']] =
                DateTimeService::getCustomDateTimeObject($appointment['bookingEnd'])
                    ->format($dateFormat . ' ' . $timeFormat);
        }

        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        if (in_array('duration', $params['fields'], true)) {
            if ($booking) {
                $row[LiteBackendStrings::getCommonStrings()['duration']] =
                    $helperService->secondsToNiceDuration(!empty($booking['duration']) ? $booking['duration'] : $appointment['service']['duration']);
            } else {
                $durations = [];
                foreach ($appointment['bookings'] as $booking2) {
                    $durations[] =
                        $helperService->secondsToNiceDuration(!empty($booking2['duration']) ? $booking2['duration'] : $appointment['service']['duration']);
                }
                $row[LiteBackendStrings::getCommonStrings()['duration']] = count(array_unique($durations)) === 1 ? $durations[0] : implode(', ', $durations);
            }
        }

        if (in_array('price', $params['fields'], true)) {
            if ($booking) {
                if ($booking['packageCustomerService']) {
                    $row[BackendStrings::getAppointmentStrings()['price']] = BackendStrings::getAppointmentStrings()['package_deal'];
                } else {
                    $row[BackendStrings::getAppointmentStrings()['price']] = $helperService->getFormattedPrice($this->getBookingPrice($booking));
                }
            } else {
                $price       = 0;
                $packageText = '';
                foreach ($appointment['bookings'] as $booking2) {
                    if ($booking2['packageCustomerService']) {
                        $packageText = BackendStrings::getAppointmentStrings()['package_deal'];
                    } else {
                        $price += $this->getBookingPrice($booking2);
                    }
                }
                if ($price > 0) {
                    if ($packageText) {
                        $row[BackendStrings::getAppointmentStrings()['price']] = $helperService->getFormattedPrice($price) . ' + ' . $packageText;
                    } else {
                        $row[BackendStrings::getAppointmentStrings()['price']] = $helperService->getFormattedPrice($price);
                    }
                } else {
                    if ($packageText) {
                        $row[BackendStrings::getAppointmentStrings()['price']] = $packageText;
                    } else {
                        $row[BackendStrings::getAppointmentStrings()['price']] = 0;
                    }
                }
            }
        }

        if (in_array('paymentAmount', $params['fields'], true)) {
            if ($booking) {
                $row[BackendStrings::getCommonStrings()['payment_amount']] = !empty($booking['payments']) ?
                    $helperService->getFormattedPrice(array_sum(array_column($booking['payments'], 'amount'))) : '';
            } else {
                $amounts = [];
                foreach ($appointment['bookings'] as $booking2) {
                    $amounts[] = !empty($booking2['payments']) ?
                        $helperService->getFormattedPrice(array_sum(array_column($booking2['payments'], 'amount'))) : '';
                }
                $row[BackendStrings::getCommonStrings()['payment_amount']] = implode(', ', $amounts);
            }
        }

        if (in_array('paymentStatus', $params['fields'], true)) {
            /** @var PaymentApplicationService $paymentAS */
            $paymentAS = $this->container->get('application.payment.service');
            if ($booking) {
                $status = $booking['payments'] && count($booking['payments']) > 0 ?
                    $paymentAS->getFullStatus($booking, Entities::APPOINTMENT) : 'pending';
                $row[BackendStrings::getCommonStrings()['payment_status']] =
                    $status === 'partiallyPaid' ? BackendStrings::getCommonStrings()['partially_paid'] : BackendStrings::getCommonStrings()[$status];
            } else {
                $statuses = [];
                foreach ($appointment['bookings'] as $booking2) {
                    $status     = $booking2['payments'] && count($booking2['payments']) > 0 ?
                        $paymentAS->getFullStatus($booking2, Entities::APPOINTMENT) : 'pending';
                    $statuses[] =
                        $status === 'partiallyPaid' ? BackendStrings::getCommonStrings()['partially_paid'] : BackendStrings::getCommonStrings()[$status];
                }
                $row[BackendStrings::getCommonStrings()['payment_status']] = implode(', ', $statuses);
            }
        }

        if (in_array('paymentMethod', $params['fields'], true)) {
            if ($booking) {
                $methodsUsed = array_map(
                    function ($payment) {
                        $method = $payment['gateway'];
                        if ($method === 'wc') {
                            $method = 'wc_name';
                        }
                        return !$method || $method === 'onSite' ? BackendStrings::getCommonStrings()['on_site'] : BackendStrings::getSettingsStrings()[$method];
                    },
                    $booking['payments']
                );

                $row[BackendStrings::getCommonStrings()['payment_method']] =
                    count(array_unique($methodsUsed)) === 1 ? $methodsUsed[0] : implode(', ', $methodsUsed);
            } else {
                $methods = [];
                foreach ($appointment['bookings'] as $booking2) {
                    $methodsUsed = array_map(
                        function ($payment) {
                            $method = $payment['gateway'];
                            if ($method === 'wc') {
                                $method = 'wc_name';
                            }
                            return !$method || $method === 'onSite' ?
                                BackendStrings::getCommonStrings()['on_site'] :
                                BackendStrings::getSettingsStrings()[$method];
                        },
                        $booking2['payments']
                    );
                    $methods[]   = count(array_unique($methodsUsed)) === 1 ? $methodsUsed[0] : implode('/', $methodsUsed);
                }
                $row[BackendStrings::getCommonStrings()['payment_method']] = implode(', ', $methods);
            }
        }

        if (in_array('wcOrderId', $params['fields'], true)) {
            if ($booking) {
                $wcOrderId = $booking['payments'] && count($booking['payments']) > 0 ?
                    implode(', ', array_column($booking['payments'], 'wcOrderId')) : '';
                $row[BackendStrings::getCommonStrings()['wc_order_id_export']] = $wcOrderId;
            } else {
                $wcOrderIds = [];
                foreach ($appointment['bookings'] as $bookingWc) {
                    $wcOrderId    = $bookingWc['payments'] && count($bookingWc['payments']) > 0 ?
                        implode('/', array_column($bookingWc['payments'], 'wcOrderId')) : '';
                    $wcOrderIds[] = $wcOrderId;
                }
                $row[BackendStrings::getCommonStrings()['wc_order_id_export']] = implode(', ', $wcOrderIds);
            }
        }

        if (in_array('note', $params['fields'], true)) {
            $row[BackendStrings::getCommonStrings()['note']] = $appointment['internalNotes'];
        }

        if (in_array('status', $params['fields'], true)) {
            if ($booking) {
                $row[BackendStrings::getCommonStrings()['status']] =
                    ucfirst(BackendStrings::getCommonStrings()[$booking['status']]);
            } else {
                $row[BackendStrings::getCommonStrings()['status']] =
                    ucfirst(BackendStrings::getCommonStrings()[$appointment['status']]);
            }
        }

        if (in_array('persons', $params['fields'], true)) {
            $row[BackendStrings::getNotificationsStrings()['ph_booking_number_of_persons']] =
                implode(', ', $numberOfPersons);
        }

        if (in_array('couponCode', $params['fields'], true)) {
            if ($booking) {
                $row[BackendStrings::getCommonStrings()['coupon_code']] = ($booking['coupon'] ? $booking['coupon']['code'] : '');
            } else {
                $couponCodes = [];
                foreach ($appointment['bookings'] as $booking2) {
                    $couponCodes[] = ($booking2['coupon'] ? $booking2['coupon']['code'] : '');
                }
                $row[BackendStrings::getCommonStrings()['coupon_code']] = implode(', ', $couponCodes);
            }
        }
    }

    private function getBookingPrice($booking)
    {
        if ($booking['status'] === BookingStatus::APPROVED || $booking['status'] === BookingStatus::PENDING) {
            /** @var PaymentApplicationService $paymentAS */
            $paymentAS = $this->container->get('application.payment.service');

            return $paymentAS->calculateAppointmentPrice($booking, Entities::APPOINTMENT);
        }
        return 0;
    }
}
