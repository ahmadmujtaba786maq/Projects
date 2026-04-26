<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\Reservation\AbstractReservationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Cache\Cache;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Factory\Cache\CacheFactory;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Cache\CacheRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class MolliePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class MolliePaymentCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'bookings',
        'payment'
    ];

    /**
     * @param MolliePaymentCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerException
     */
    public function handle(MolliePaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var AbstractReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var PaymentServiceInterface $paymentService */
        $paymentService = $this->container->get('infrastructure.payment.mollie.service');

        /** @var CacheRepository $cacheRepository */
        $cacheRepository = $this->container->get('domain.cache.repository');

        $bookingData = $bookingAS->getAppointmentData($command->getFields());

        $bookingData = apply_filters('amelia_before_mollie_redirect_filter', $bookingData);

        do_action('amelia_before_mollie_redirect', $bookingData);

        /** @var Reservation $reservation */
        $reservation = $reservationService->getNew(true, true, true);

        $reservationService->processBooking(
            $result,
            $bookingData,
            $reservation,
            false
        );

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }


        $paymentAmount = $reservationService->getReservationPaymentAmount($reservation);

        if (!$paymentAmount) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData(
                [
                    'paymentSuccessful' => false,
                    'onSitePayment'     => true
                ]
            );

            return $result;
        }


        $token = new Token();

        /** @var Cache $cache */
        $cache = CacheFactory::create(
            [
                'name' => $token->getValue(),
                'data' => json_encode(
                    [
                        'status'  => null,
                        'request' => $command->getField('componentProps'),
                    ]
                ),
            ]
        );

        $cacheId = $cacheRepository->add($cache);

        $cache->setId(new Id($cacheId));

        /** @var Reservation $reservation */
        $reservation = $reservationService->getNew(true, true, true);

        $result = $reservationService->processRequest(
            $bookingAS->getAppointmentData($command->getFields()),
            $reservation,
            true
        );

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }

        $additionalInformation = $paymentAS->getBookingInformationForPaymentSettings(
            $reservation,
            PaymentType::MOLLIE
        );

        $identifier = $cacheId . '_' . $token->getValue() . '_' . $type;

        $transfers = [];

        $returnUrl = explode('#', $command->getField('returnUrl'));
        try {
            $response = $paymentService->execute(
                [
                    'returnUrl'   =>
                        $returnUrl[0] . (strpos($returnUrl[0], '?') ? '&' : '?') . 'ameliaCache=' . $identifier .
                        (!empty($returnUrl[1]) ? '#' . $returnUrl[1] : ''),
                    'notifyUrl'   =>
                        (AMELIA_DEV ? str_replace('localhost', AMELIA_NGROK_URL, AMELIA_ACTION_URL) : AMELIA_ACTION_URL) .
                        '/payment/mollie/notify&name=' . $identifier,
                    'amount'      => $paymentAmount,
                    'locale'      => str_replace('-', '_', $reservation->getLocale()->getValue()),
                    'description' => $additionalInformation['description'] ?:
                        $reservation->getBookable()->getName()->getValue(),
                    'method'      => $settingsService->getSetting('payments', 'mollie')['method'],
                    'metaData'    => $additionalInformation['metaData'] ?: [],
                ],
                $transfers
            );
        } catch (Exception $e) {
            $reservationService->deleteReservation($reservation);


            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData(
                [
                    'message' => $e->getMessage(),
                    'paymentSuccessful' => false,
                ]
            );

            return $result;
        }


        if ($response->isRedirect()) {
            $result = $paymentAS->updateCache($result, $command->getFields(), $cache, $reservation);

            $bookings = [];

            if ($result->getData()['type'] !== 'package') {
                $bookings[] = [
                    'id' => $result->getData()['booking']['id'],
                    'token' => $result->getData()['booking']['token']
                ];
                $recurringBookings = $result->getData()['recurring'] ?? [];
                foreach ($recurringBookings as $recurring) {
                    $bookings[] = [
                        'id'    => $recurring['booking']['id'],
                        'token' => $recurring['booking']['token'],
                    ];
                }
            }

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Proceed to Mollie Payment Page');
            $result->setData(
                [
                    'redirectUrl' => $response->getRedirectUrl(),
                    'bookings' => $bookings,
                    'packageCustomer' => $result->getData()['packageCustomerId'] ? [
                        'id' => $result->getData()['packageCustomerId'],
                        'token' => $result->getData()['packageCustomerToken'] ?: '',
                    ] : null
                ]
            );

            return $result;
        }

        $reservationService->deleteReservation($reservation);

        $result->setResult(CommandResult::RESULT_ERROR);
        $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
        $result->setData(
            [
                'message' => $response->getMessage() && json_decode($response->getMessage(), true) !== false ?
                    json_decode($response->getMessage(), true)['detail'] : '',
                'paymentSuccessful' => false,
            ]
        );

        return $result;
    }
}
