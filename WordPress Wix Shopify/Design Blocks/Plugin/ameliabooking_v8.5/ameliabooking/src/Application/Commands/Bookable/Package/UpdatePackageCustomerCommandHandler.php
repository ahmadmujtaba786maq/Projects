<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Package;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdatePackageCustomerCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Package
 */
class UpdatePackageCustomerCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdatePackageCustomerCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function handle(UpdatePackageCustomerCommand $command)
    {
        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        /** @var AbstractUser|null $user */
        $user = null;

        if (!$command->getPermissionService()->currentUserCanWrite(Entities::PACKAGES)) {
            /** @var AbstractUser $user */
            $user = $userAS->getAuthenticatedUser($command->getToken(), false, 'customerCabinet');

            if ($user === null) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not retrieve user');
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );

                return $result;
            }
        }

        $this->checkMandatoryFields($command);

        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        $packageCustomerId = $command->getArg('id');

        $packageCustomerId = apply_filters('amelia_before_package_customer_status_updated_filter', $packageCustomerId, $command->getField('status'));

        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = $packageCustomerRepository->getById($packageCustomerId);

        if ($user && $packageCustomer->getCustomerId()->getValue() !== $user->getId()->getValue()) {
            throw new AccessDeniedException('You are not allowed to update status');
        }

        do_action('amelia_before_package_customer_status_updated', $packageCustomer->toArray(), $command->getField('status'));

        $packageCustomerRepository->updateFieldById(
            $command->getArg('id'),
            $command->getField('status'),
            'status'
        );

        do_action('amelia_after_package_customer_status_updated', $packageCustomer->toArray(), $command->getField('status'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated package');
        $result->setData(
            [
                'packageCustomerId' => $command->getArg('id'),
                'status'            => $command->getField('status')
            ]
        );

        return $result;
    }
}
