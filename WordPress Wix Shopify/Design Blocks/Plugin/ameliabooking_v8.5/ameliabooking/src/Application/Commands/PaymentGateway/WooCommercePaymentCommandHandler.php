<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class WooCommercePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class WooCommercePaymentCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'payment'
    ];

    /**
     * @param WooCommercePaymentCommand $command
     *
     * @return CommandResult
     * @throws ForbiddenFileUploadException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(WooCommercePaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        WooCommerceService::setContainer($this->container);

        $data = $command->getFields();

        $data['isCart'] = !empty($data['isCart']) && !!$data['isCart'];

        $reservation = $reservationService->getNew(true, true, true);

        $appointmentData = $bookingAS->getAppointmentData($data);

        $reservationService->processBooking(
            $result,
            $appointmentData,
            $reservation,
            false
        );

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        $uploadedCustomFieldFilesNames = $customFieldService->saveUploadedFiles(
            0,
            $reservation->getUploadedCustomFieldFilesInfo(),
            '/tmp',
            false
        );

        $uploadedCustomerCustomFieldFilesNames = $customFieldService->saveUploadedFiles(
            0,
            $reservation->getUploadedCustomerCustomFieldFilesInfo(),
            '/tmp',
            false
        );

        $appointmentData = $reservationService->getWooCommerceData(
            $reservation,
            $data['payment']['gateway'],
            $appointmentData
        );

        $appointmentData['uploadedCustomFieldFilesInfo'] = $uploadedCustomFieldFilesNames;

        $appointmentData['uploadedCustomerCustomFieldFilesNames'] = $uploadedCustomerCustomFieldFilesNames;

        $appointmentData['returnUrl'] = $command->getField('returnUrl');

        $componentProps = $command->getField('componentProps');

        $customFields = [];

        switch ($command->getField('type')) {
            case Entities::APPOINTMENT:
            case Entities::PACKAGE:
                $customFields = !empty($componentProps['state']) ?
                    $componentProps['state']['appointment']['bookings'][0]['customFields'] :
                    $componentProps['appointment']['bookings'][0]['customFields'];

                break;

            case Entities::EVENT:
                $customFields = !empty($componentProps['state']) ?
                    $componentProps['state']['customFields']['customFields'] :
                    $componentProps['appointment']['bookings'][0]['customFields'];

                break;
        }

        foreach ($customFields as &$customField) {
            $customField['label'] = !empty($customField['label']) ? htmlspecialchars($customField['label'], ENT_QUOTES, 'UTF-8') : $customField['label'];
            if (is_array($customField['value'])) {
                foreach ($customField['value'] as &$value) {
                    $value = !empty($value) ? htmlspecialchars((!empty($value['name']) ? $value['name'] : $value), ENT_QUOTES, 'UTF-8') : $value;
                }
            } else {
                $customField['value'] = !empty($customField['value']) ? htmlspecialchars($customField['value'], ENT_QUOTES, 'UTF-8') : $customField['value'];
            }
        }

        switch ($command->getField('type')) {
            case Entities::APPOINTMENT:
            case Entities::PACKAGE:
                if (!empty($componentProps['state'])) {
                    $componentProps['state']['appointment']['bookings'][0]['customFields'] = $customFields;

                    if (!empty($componentProps['state']['appointment']['bookings'][0]['customer']['translations'])) {
                        $componentProps['state']['appointment']['bookings'][0]['customer']['translations'] = null;
                    }
                } else {
                    $componentProps['appointment']['bookings'][0]['customFields'] = $customFields;

                    if (!empty($componentProps['appointment']['bookings'][0]['customer']['translations'])) {
                        $componentProps['appointment']['bookings'][0]['customer']['translations'] = null;
                    }
                }

                break;

            case Entities::EVENT:
                if (!empty($componentProps['state'])) {
                    $componentProps['state']['customFields']['customFields'] = $customFields;

                    if (!empty($componentProps['state']['customerInfo']['translations'])) {
                        $componentProps['state']['customerInfo']['translations'] = null;
                    }

                    if (
                        !empty($componentProps['state']['tickets']['tickets']) &&
                        is_array($componentProps['state']['tickets']['tickets'])
                    ) {
                        foreach ($componentProps['state']['tickets']['tickets'] as $key => $ticket) {
                            if (!empty($componentProps['state']['tickets']['tickets'][$key]['dateRanges'])) {
                                $componentProps['state']['tickets']['tickets'][$key]['dateRanges'] = json_decode(
                                    $componentProps['state']['tickets']['tickets'][$key]['dateRanges'],
                                    true
                                );
                            }
                        }
                    }
                } else {
                    $componentProps['appointment']['bookings'][0]['customFields'] = $customFields;

                    if (!empty($componentProps['appointment']['bookings'][0]['customer']['translations'])) {
                        $componentProps['appointment']['bookings'][0]['customer']['translations'] = null;
                    }
                }

                break;
        }

        if (!empty($componentProps['state']['appointment']['bookings'][0]['customer']['customFields'])) {
            $componentProps['state']['appointment']['bookings'][0]['customer']['customFields'] = null;
        }

        if (!empty($componentProps['state']['customerInfo']['customFields'])) {
            $componentProps['state']['customerInfo']['customFields'] = null;
        }

        $appointmentData['cacheData'] = json_encode(
            [
                'status'  => null,
                'request' => $componentProps
            ]
        );

        try {
            $bookableSettings = $reservation->getBookable()->getSettings() ?
                json_decode($reservation->getBookable()->getSettings()->getValue(), true) : null;

            $appointmentData['wcProductId'] = $bookableSettings && isset($bookableSettings['payments']['wc']['productId']) ?
                $bookableSettings['payments']['wc']['productId'] : null;

            $appointmentData = apply_filters('amelia_before_wc_cart_filter', $appointmentData);

            do_action('amelia_before_wc_cart', $appointmentData);

            WooCommerceService::addToCart($appointmentData);
        } catch (Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['wc_error']);
            $result->setData(
                [
                    'wooCommerceError' => true
                ]
            );

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Proceed to WooCommerce Cart');
        $result->setData(
            [
                'cartUrl' => WooCommerceService::getPageUrl($appointmentData)
            ]
        );

        return $result;
    }
}
