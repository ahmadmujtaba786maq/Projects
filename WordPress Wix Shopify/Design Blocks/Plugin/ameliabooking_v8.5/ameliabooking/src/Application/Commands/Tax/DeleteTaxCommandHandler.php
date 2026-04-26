<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Tax;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Tax\TaxApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxRepository;

/**
 * Class DeleteTaxCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Tax
 */
class DeleteTaxCommandHandler extends CommandHandler
{
    /**
     * @param DeleteTaxCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    public function handle(DeleteTaxCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::TAXES)) {
            throw new AccessDeniedException('You are not allowed to delete taxes.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        /** @var TaxApplicationService $taxAS */
        $taxAS = $this->container->get('application.tax.service');

        /** @var Tax $tax */
        $tax = $taxRepository->getById($command->getArg('id'));

        $taxRepository->beginTransaction();

        if (!$taxAS->delete($tax)) {
            $taxRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete tax.');

            return $result;
        }

        $taxRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Tax successfully deleted.');
        $result->setData(
            [
                Entities::TAX => $tax->toArray(),
            ]
        );

        return $result;
    }
}
