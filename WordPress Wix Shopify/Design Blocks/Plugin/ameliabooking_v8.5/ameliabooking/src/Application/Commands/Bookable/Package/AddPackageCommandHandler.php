<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Package;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\Gallery\GalleryApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageFactory;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageServiceFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddPackageCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Package
 */
class AddPackageCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'name',
        'price',
        'calculatedPrice',
        'bookable',
    ];

    /**
     * @param AddPackageCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(AddPackageCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::PACKAGES)) {
            throw new AccessDeniedException('You are not allowed to add package.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $packageData = $command->getFields();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');
        /** @var BookableApplicationService $bookableService */
        $bookableService = $this->container->get('application.bookable.service');
        /** @var GalleryApplicationService $galleryService */
        $galleryService = $this->container->get('application.gallery.service');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntitiesForPackage($packageData);

        $bookableServices = [];

        foreach ($packageData['bookable'] as $bookable) {
            $bookableServices[$bookable['service']['id']] = [
                'quantity'         => $bookable['quantity'],
                'minimumScheduled' => $bookable['minimumScheduled'],
                'maximumScheduled' => $bookable['maximumScheduled'],
                'allowProviderSelection' => $bookable['allowProviderSelection'],
                'providers'        => $bookable['providers'],
                'locations'        => $bookable['locations']
            ];
        }

        /** @var Collection $services */
        $services = $serviceRepository->getByCriteria(['services' => array_keys($bookableServices)]);


        $packageData = apply_filters('amelia_before_package_added_filter', $packageData);

        do_action('amelia_before_package_added', $packageData);

        /** @var Package $package */
        $package = PackageFactory::create(array_merge($packageData, ['bookable' => []]));

        if (!($package instanceof Package)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create package.');

            return $result;
        }

        foreach ($bookableServices as $serviceId => $data) {
            $package->getBookable()->addItem(
                PackageServiceFactory::create(
                    [
                        'service'          => $services->getItem($serviceId)->toArray(),
                        'quantity'         => $data['quantity'],
                        'minimumScheduled' => $data['minimumScheduled'],
                        'maximumScheduled' => $data['maximumScheduled'],
                        'allowProviderSelection' => $data['allowProviderSelection'],
                        'providers'        => $data['providers'],
                        'locations'        => $data['locations'],
                    ]
                )
            );
        }

        $packageRepository->beginTransaction();

        if (!($packageId = $packageRepository->add($package))) {
            $packageRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create package.');

            return $result;
        }

        $package->setId(new Id($packageId));

        $bookableService->manageServicesForPackageAdd($package);
        $galleryService->manageGalleryForEntityAdd($package->getGallery(), $packageId);

        $packageRepository->commit();

        do_action('amelia_after_package_added', $package->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new package.');
        $result->setData(
            [
                Entities::PACKAGE => $package->toArray(),
            ]
        );

        return $result;
    }
}
