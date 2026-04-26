<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Extra\AbstractExtraApplicationService;
use AmeliaBooking\Application\Services\Gallery\GalleryApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ProviderServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateServiceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class UpdateServiceCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'categoryId',
        'duration',
        'maxCapacity',
        'minCapacity',
        'name',
        'price',
        'applyGlobally',
        'providers'
    ];

    /**
     * @param UpdateServiceCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(UpdateServiceCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update service.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $serviceData = $command->getFields();

        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntitiesForService($serviceData);

        $serviceData = apply_filters('amelia_before_service_updated_filter', $serviceData);

        do_action('amelia_before_service_updated', $serviceData);

        /** @var Service $service */
        $service = ServiceFactory::create($serviceData);

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        if ($service->getSettings()) {
            $newSettings = new Json(
                json_encode(
                    array_merge(
                        json_decode($service->getSettings()->getValue(), true),
                        ['activation' => ['version' => $settingsService->getSetting('activation', 'version')]]
                    )
                )
            );

            $service->setSettings($newSettings);
        }

        if (!($service instanceof Service)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to update service.');

            return $result;
        }

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ProviderServiceRepository $providerServiceRepository */
        $providerServiceRepository = $this->container->get('domain.bookable.service.providerService.repository');
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');

        /** @var Category $category */
        $category = $categoryRepository->getById($service->getCategoryId()->getValue());

        /** @var BookableApplicationService $bookableService */
        $bookableService = $this->container->get('application.bookable.service');
        /** @var AbstractExtraApplicationService $extraService */
        $extraService = $this->container->get('application.extra.service');
        /** @var GalleryApplicationService $galleryService */
        $galleryService = $this->container->get('application.gallery.service');

        $serviceRepository->beginTransaction();

        if (
            $command->getField('applyGlobally') &&
            !$providerServiceRepository->updateServiceForAllProviders($service, $command->getArg('id'))
        ) {
            $serviceRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to update service.');

            return $result;
        }

        if (!$category || !$serviceRepository->update($command->getArg('id'), $service)) {
            $serviceRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to update service.');

            return $result;
        }

        $bookableService->manageProvidersForServiceUpdate(
            $service,
            $serviceData['providers'],
            !$command->getField('applyGlobally')
        );

        $extraService->manageExtrasForServiceUpdate($service);

        $bookableService->managePackagesForServiceUpdate($service);

        $galleryService->manageGalleryForEntityUpdate(
            $service->getGallery(),
            $command->getArg('id'),
            Entities::SERVICE
        );

        $serviceRepository->commit();

        do_action('amelia_after_service_updated', $service->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated service.');
        $result->setData(
            [
                Entities::SERVICE => $service->toArray(),
            ]
        );

        return $result;
    }
}
