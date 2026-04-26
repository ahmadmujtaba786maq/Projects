<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Payment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Services\Payment\SquareService;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class CalculatePaymentAmountCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class CalculatePaymentAmountCommandHandler extends CommandHandler
{
    /**
     * @param CalculatePaymentAmountCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(CalculatePaymentAmountCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($command->getField('type'));

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        $squareSettings = $settingsService->getCategorySettings('payments')['square'];

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

        $transfers = [];

        $paymentAS->setTransfers(
            $bookingAS->getAppointmentData($command->getFields())['payment'],
            $reservation,
            new BookingType($command->getField('type')),
            $transfers,
            false
        );

        $paymentAmount = $reservationService->getReservationPaymentAmount($reservation);

        $countryCode = null;
        if ($squareSettings['enabled']) {
            /** @var SquareService $squareService */
            $squareService = $this->container->get('infrastructure.payment.square.service');

            $countryCode = $squareService->getCountryCodeByLocationId($squareSettings['locationId']);
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setData(
            [
                'amount'      => $paymentAmount,
                'currency'    => $settingsService->getCategorySettings('payments')['currency'],
                'transfers'   => $transfers,
                'countryCode' => $countryCode,
            ]
        );

        return $result;
    }
}
