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
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class DeletePackageCustomerCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Package
 */
class DeletePackageCustomerCommandHandler extends CommandHandler
{
    /**
     * @param DeletePackageCustomerCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(DeletePackageCustomerCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::PACKAGES)) {
            throw new AccessDeniedException('You are not allowed to delete packages.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var BookableApplicationService $bookableApplicationService */
        $bookableApplicationService = $this->getContainer()->get('application.bookable.service');

        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = $packageCustomerRepository->getById($command->getArg('id'));

        $packageRepository->beginTransaction();

        do_action('amelia_before_package_customer_deleted', $packageCustomer->toArray());

        if (($resultData = $bookableApplicationService->deletePackageCustomer($packageCustomer)) === false) {
            $packageRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete package.');

            return $result;
        }

        $packageRepository->commit();

        do_action('amelia_after_package_customer_deleted', $packageCustomer->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted package purchase.');
        $result->setData(
            [
                'packageCustomer' => $packageCustomer->toArray(),
                'appointments'    => $resultData,
            ]
        );

        return $result;
    }
}
