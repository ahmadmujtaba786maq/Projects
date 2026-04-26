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
use AmeliaBooking\Domain\Factory\Tax\TaxFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateTaxCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Tax
 */
class UpdateTaxCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'name',
        'type',
        'amount',
        'status',
        'extras',
        'services',
        'events',
        'packages',
    ];

    /**
     * @param UpdateTaxCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(UpdateTaxCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::TAXES)) {
            throw new AccessDeniedException('You are not allowed to update tax.');
        }

        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        /** @var TaxApplicationService $taxAS */
        $taxAS = $this->container->get('application.tax.service');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var Tax $tax */
        $tax = TaxFactory::create($command->getFields());

        if (!($tax instanceof Tax)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update tax.');

            return $result;
        }

        $taxAS->setTaxEntities($tax, $command->getFields());

        $taxRepository->beginTransaction();

        $taxAS->update($tax);

        $taxRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Tax successfully updated.');
        $result->setData(
            [
                Entities::TAX => $tax->toArray(),
            ]
        );

        return $result;
    }
}
