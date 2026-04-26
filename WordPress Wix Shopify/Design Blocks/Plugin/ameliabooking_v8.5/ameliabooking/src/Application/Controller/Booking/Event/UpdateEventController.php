<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\UpdateEventCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateEventController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class UpdateEventController extends Controller
{
    /**
     * Fields for event that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'id',
        'parentId',
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
        'customLocation',
        'settings',
        'applyGlobally',
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
        'notifyParticipants',
        'aggregatedPrice'
    ];

    /**
     * Instantiates the Update Event command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateEventCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateEventCommand($args);

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
            $eventBus->emit('EventEdited', $result);
        }
    }
}
