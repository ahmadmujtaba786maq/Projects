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
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\Extra\AbstractExtraApplicationService;
use AmeliaBooking\Application\Services\Gallery\GalleryApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddServiceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class AddServiceCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'categoryId',
        'duration',
        'maxCapacity',
        'minCapacity',
        'name',
        'price',
        'providers'
    ];

    /**
     * @param AddServiceCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(AddServiceCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to add service.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $serviceData = $command->getFields();

        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntitiesForService($serviceData);

        $serviceData = apply_filters('amelia_before_service_added_filter', $serviceData);

        do_action('amelia_before_service_added', $serviceData);

        /** @var Service $service */
        $service = ServiceFactory::create($serviceData);

        if (!($service instanceof Service)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create service.');

            return $result;
        }

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var BookableApplicationService $bookableService */
        $bookableService = $this->container->get('application.bookable.service');
        /** @var AbstractExtraApplicationService $extraService */
        $extraService = $this->container->get('application.extra.service');
        /** @var GalleryApplicationService $galleryService */
        $galleryService = $this->container->get('application.gallery.service');
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');

        $serviceRepository->beginTransaction();

        /** @var Category $category */
        $category = $categoryRepository->getById($service->getCategoryId()->getValue());

        if (!$category || !($serviceId = $serviceRepository->add($service))) {
            $serviceRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create service.');

            return $result;
        }

        $service->setId(new Id($serviceId));

        /** @var Collection $providers */
        $providers = $command->getField('providers') ?
            $providerRepository->getFiltered(['providers' => $command->getField('providers')], 0) : new Collection();

        $bookableService->manageProvidersForServiceAdd($service, $providers);
        $extraService->manageExtrasForServiceAdd($service);
        $galleryService->manageGalleryForEntityAdd($service->getGallery(), $serviceId);

        $serviceRepository->commit();

        do_action('amelia_after_service_added', $service->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new service.');
        $result->setData(
            [
                Entities::SERVICE => $service->toArray(),
            ]
        );

        return $result;
    }
}
