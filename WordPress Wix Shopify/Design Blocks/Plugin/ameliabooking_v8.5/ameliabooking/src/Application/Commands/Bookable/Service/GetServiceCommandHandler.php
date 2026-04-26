<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetServiceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class GetServiceCommandHandler extends CommandHandler
{
    /**
     * @param GetServiceCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function handle(GetServiceCommand $command)
    {
        $result = new CommandResult();

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Service $service */
        $service = $serviceRepository->getByCriteria(
            ['services' => [$command->getArg('id')]]
        )->getItem($command->getArg('id'));

        // fix for wrongly saved JSON
        if (
            $service->getSettings() &&
            json_decode($service->getSettings()->getValue(), true) === null
        ) {
            $service->setSettings(null);
        }

        if ($service->getSettings()) {
            /** @var SettingsService $settingsDS */
            $settingsDS = $this->container->get('domain.settings.service');

            $service->setSettings(new Json(json_encode($settingsDS->getSavedSettings($service->getSettings()))));
        }

        $futureAppointmentsProvidersIds = $appointmentRepository->getFutureAppointmentsProvidersIds(
            [$service->getId()->getValue()],
            DateTimeService::getNowDateTime(),
            null
        );

        $serviceArray = $service->toArray();

        $serviceArray = apply_filters('amelia_get_service_filter', $serviceArray);

        do_action('amelia_get_service', $serviceArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved service.');
        $result->setData(
            [
                Entities::SERVICE                => $serviceArray,
                'futureAppointmentsProvidersIds' => $futureAppointmentsProvidersIds,
            ]
        );

        return $result;
    }
}
