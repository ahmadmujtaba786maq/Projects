<?php

namespace AmeliaBooking\Application\Commands\Square;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Cache\Cache;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Cache\CacheFactory;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Cache\CacheRepository;
use AmeliaBooking\Infrastructure\Services\Payment\CurrencyService;
use AmeliaBooking\Infrastructure\Services\Payment\SquareService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;
use Square\Models\PaymentLink;

/**
 * Class SquarePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Square
 */
class SquarePaymentCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'bookings',
        'payment'
    ];

    /**
     * @param SquarePaymentCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerException
     */
    public function handle(SquarePaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var SquareService $paymentService */
        $paymentService = $this->container->get('infrastructure.payment.square.service');

        /** @var CacheRepository $cacheRepository */
        $cacheRepository = $this->container->get('domain.cache.repository');

        /** @var CurrencyService $currencyService */
        $currencyService = $this->container->get('infrastructure.payment.currency.service');

        /** @var Reservation $reservation */
        $reservation = $reservationService->getNew(true, true, true);

        $reservationService->processBooking(
            $result,
            $bookingAS->getAppointmentData($command->getFields()),
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


        $additionalInformation = $paymentAS->getBookingInformationForPaymentSettings(
            $reservation,
            PaymentType::SQUARE
        );

        $identifier = $cacheId . '_' . $token->getValue() . '_' . $type;

        $returnUrl = explode('#', $command->getField('returnUrl'));
        $bookings  = $command->getField('bookings');

        $redirectUrl = AMELIA_ACTION_URL . '__payment__square__notify&name=' . $identifier . '&returnUrl=' . $returnUrl[0];

        $response = null;

        $transfers = [];

        try {
            $response = $paymentService->execute(
                [
                    'redirectUrl' => $redirectUrl,
                    'amount'      => $currencyService->getAmountInFractionalUnit(new Price($paymentAmount)),
                    'description' => $additionalInformation['description'] ?: $reservation->getBookable()->getName()->getValue(),
                    'metaData'    => $additionalInformation['metaData'] ?: [],
                    'customer'    => $bookings ? $bookings[0]['customer'] : null
                ],
                $transfers
            );
        } catch (Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData(
                [
                    'message' => $e->getMessage(),
                    'paymentSuccessful' => false,
                ]
            );
        }

        if ($response && $response->isSuccess() && $response->getResult() && $response->getResult()->getPaymentLink()) {
            /**@var PaymentLink $paymentLink */
            $paymentLink = $response->getResult()->getPaymentLink();
            $orderId     = $paymentLink->getOrderId();


            $result = $reservationService->processRequest(
                $bookingAS->getAppointmentData($command->getFields()),
                $reservation,
                true
            );

            $result = $paymentAS->updateCache($result, $command->getFields(), $cache, $reservation, ['orderId' => $orderId]);

            $paymentService->updatePaymentLink(
                $paymentLink,
                $redirectUrl . '&squareOrderId=' . $orderId . (!empty($returnUrl[1]) ? '&fragment=' . $returnUrl[1] : ''),
                $result->getData()['paymentId']
            );

            if ($result->getResult() === CommandResult::RESULT_ERROR) {
                return $result;
            }

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Proceed to Square Checkout Page');
            $result->setData(
                [
                    'redirectUrl' => $paymentLink->getUrl()
                ]
            );

            return $result;
        }

        $result->setResult(CommandResult::RESULT_ERROR);
        $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
        $result->setData(
            [
                'message' => $response ? $paymentService->getErrorMessage($response) : null,
                'paymentSuccessful' => false,
            ]
        );

        return $result;
    }
}
