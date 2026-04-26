<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\SuccessfulBookingCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class SuccessfulBookingController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class SuccessfulBookingController extends Controller
{
    /**
     * Fields for successful booking that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'type',
        'appointmentStatusChanged',
        'recurring',
        'packageId',
        'isPackageAppointment',
        'customer',
        'paymentId',
        'packageCustomerId',
        'packageBookingFromBackend',
    ];

    /**
     * Instantiates the SuccessfulBooking command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return SuccessfulBookingCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new SuccessfulBookingCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        if ($result->getData()) {
            $eventBus->emit('BookingAdded', $result);
        }
    }
}
