<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\GetTimeSlotsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetTimeSlotsController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class GetTimeSlotsController extends Controller
{
    /**
     * Fields for calendar service that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'serviceId',
        'serviceDuration',
        'weekDays',
        'startDateTime',
        'providerIds',
        'extras',
        'excludeAppointmentId',
        'persons',
        'group',
        'page',
        'monthsLoad',
        'queryTimeZone',
        'timeZone',
        'allowAdminBookAtAnyTime',
        'allowBookingIfPending',
        'allowBookingIfNotMin',
        'timeSlotLength',
        'serviceDurationAsSlot',
        'bufferTimeInSlot',
        'structured',
    ];

    /**
     * Instantiates the Get Time Slots command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetTimeSlotsCommand($args);

        $params = (array)$request->getQueryParams();

        if (!empty($params['extras'])) {
            if (($arrayExtras = json_decode($params['extras'], true)) !== null) {
                $params['extras'] = $arrayExtras;
            } else {
                $arrayExtras = [];

                foreach (explode(',', $params['extras']) as $item) {
                    $extrasData = explode('-', $item);

                    $arrayExtras[] = ['id' => $extrasData[0], 'quantity' => $extrasData[1]];
                }

                $params['extras'] = $arrayExtras;
            }
        }

        $this->setArrayParams($params);

        $command->setField('serviceId', (int)$request->getQueryParam('serviceId', 0));
        $command->setField('locationId', (int)$request->getQueryParam('locationId', 0));
        $command->setField('locationIds', !empty($params['locationIds']) ? $params['locationIds'] : []);
        $command->setField('serviceDuration', (int)$request->getQueryParam('serviceDuration', 0));
        $command->setField('weekDays', (array)$request->getQueryParam('weekDays', [1, 2, 3, 4, 5, 6, 7]));
        $command->setField('startDateTime', (string)$request->getQueryParam('startDateTime', ''));
        $command->setField('endDateTime', (string)$request->getQueryParam('endDateTime', ''));
        $command->setField('providerIds', !empty($params['providerIds']) ? $params['providerIds'] : []);
        $command->setField('extras', !empty($params['extras']) ? $params['extras'] : []);
        $command->setField('excludeAppointmentId', (int)$request->getQueryParam('excludeAppointmentId', []));
        $command->setField('persons', (int)$request->getQueryParam('persons', 1));
        $command->setField('group', (int)$request->getQueryParam('group', 0));
        $command->setField('page', (string)$request->getQueryParam('page', ''));
        $command->setField('monthsLoad', (int)$request->getQueryParam('monthsLoad', 0));
        $command->setField('queryTimeZone', (string)$request->getQueryParam('queryTimeZone', ''));
        $command->setField('timeZone', (string)$request->getQueryParam('timeZone', ''));

        $command->setField('allowAdminBookAtAnyTime', $request->getQueryParam('allowAdminBookAtAnyTime'));
        $command->setField('allowBookingIfPending', $request->getQueryParam('allowBookingIfPending'));
        $command->setField('allowBookingIfNotMin', $request->getQueryParam('allowBookingIfNotMin'));
        $command->setField('timeSlotLength', $request->getQueryParam('timeSlotLength'));
        $command->setField('serviceDurationAsSlot', $request->getQueryParam('serviceDurationAsSlot'));
        $command->setField('bufferTimeInSlot', $request->getQueryParam('bufferTimeInSlot'));
        $command->setField('structured', $request->getQueryParam('structured'));

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
