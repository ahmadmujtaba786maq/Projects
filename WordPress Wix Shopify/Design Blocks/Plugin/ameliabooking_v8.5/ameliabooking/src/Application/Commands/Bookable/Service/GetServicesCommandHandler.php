<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetServicesCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class GetServicesCommandHandler extends CommandHandler
{
    /**
     * @param GetServicesCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(GetServicesCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to read services.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get('domain.settings.service');

        $generalSettings = $settingsService->getCategorySettings('general');

        /** @var Collection $services */
        $services = $serviceRepository->getFiltered(
            array_merge(
                $command->getField('params'),
                [
                    'sort' => $generalSettings['sortingServices']
                ]
            ),
            $generalSettings['servicesPerPage']
        );

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            if ($service->getSettings() && json_decode($service->getSettings()->getValue(), true) === null) {
                $service->setSettings(null);
            }
        }

        $servicesArray = $services->toArray();

        $servicesArray = apply_filters('amelia_get_services_filter', $servicesArray);

        do_action('amelia_get_services', $servicesArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved services.');
        $result->setData(
            [
                Entities::SERVICES => $servicesArray,
                'countFiltered'    => (int)$serviceRepository->getCount($command->getField('params')),
                'countTotal'       => (int)$serviceRepository->getCount([]),
            ]
        );

        return $result;
    }
}
