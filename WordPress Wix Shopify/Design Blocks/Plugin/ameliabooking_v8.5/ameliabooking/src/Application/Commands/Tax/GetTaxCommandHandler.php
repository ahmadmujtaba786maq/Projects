<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Tax;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxRepository;

/**
 * Class GetTaxCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Tax
 */
class GetTaxCommandHandler extends CommandHandler
{
    /**
     * @param GetTaxCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     */
    public function handle(GetTaxCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::TAXES)) {
            throw new AccessDeniedException('You are not allowed to read tax.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $taxId = $command->getArg('id');

        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        /** @var Tax $tax */
        $tax = $taxRepository->getById($taxId);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved tax.');
        $result->setData(
            [
                Entities::TAX => $tax->toArray(),
            ]
        );

        return $result;
    }
}
