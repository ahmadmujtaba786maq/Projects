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

/**
 * Class AddTaxCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Tax
 */
class AddTaxCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'name',
        'type',
        'amount',
        'status',
        'services',
        'extras',
        'events',
        'packages',
    ];

    /**
     * @param AddTaxCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws NotFoundException
     */
    public function handle(AddTaxCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::TAXES)) {
            throw new AccessDeniedException('You are not allowed to add new tax.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        /** @var TaxApplicationService $taxAS */
        $taxAS = $this->container->get('application.tax.service');

        /** @var Tax $tax */
        $tax = TaxFactory::create($command->getFields());

        if (!($tax instanceof Tax)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to create tax.');

            return $result;
        }

        $taxAS->setTaxEntities($tax, $command->getFields());

        $taxRepository->beginTransaction();

        if (!$taxAS->add($tax)) {
            $taxRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to create tax.');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('New tax successfully created.');
        $result->setData(
            [
                Entities::TAX => $tax->toArray(),
            ]
        );

        $taxRepository->commit();

        return $result;
    }
}
