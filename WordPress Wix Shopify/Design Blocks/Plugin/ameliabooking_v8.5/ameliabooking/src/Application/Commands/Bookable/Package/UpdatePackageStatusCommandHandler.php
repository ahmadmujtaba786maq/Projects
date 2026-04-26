<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Package;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdatePackageStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Package
 */
class UpdatePackageStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdatePackageStatusCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws QueryExecutionException
     */
    public function handle(UpdatePackageStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::PACKAGES)) {
            throw new AccessDeniedException('You are not allowed to update package.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        do_action('amelia_before_package_status_updated', $command->getArg('id'), $command->getField('status'));

        $packageRepository->updateStatusById(
            $command->getArg('id'),
            $command->getField('status')
        );

        do_action('amelia_after_package_status_updated', $command->getArg('id'), $command->getField('status'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated package');
        $result->setData(true);

        return $result;
    }
}
