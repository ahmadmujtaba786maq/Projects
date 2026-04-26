<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\AddEventCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class AddEventController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class AddEventController extends Controller
{
    /**
     * Fields for appointment that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'name',
        'periods',
        'bookingOpens',
        'bookingCloses',
        'bookingOpensRec',
        'bookingClosesRec',
        'recurring',
        'bringingAnyone',
        'bookMultipleTimes',
        'maxCapacity',
        'maxCustomCapacity',
        'maxExtraPeople',
        'price',
        'providers',
        'tags',
        'description',
        'gallery',
        'color',
        'show',
        'locationId',
        'settings',
        'customLocation',
        'zoomUserId',
        'organizerId',
        'translations',
        'deposit',
        'depositPayment',
        'depositPerPerson',
        'timeZone',
        'utc',
        'customTickets',
        'fullPayment',
        'customPricing',
        'closeAfterMin',
        'closeAfterMinBookings',
        'aggregatedPrice'
    ];

    /**
     * Instantiates the Add Event command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddEventCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddEventCommand($args);

        $requestBody = $request->getParsedBody();

        $this->filter($requestBody);
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
        }

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
        if ($result->getResult() === CommandResult::RESULT_SUCCESS) {
            $eventBus->emit('EventAdded', $result);
        }
    }
}
