<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Tax;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxRepository;
use Slim\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetTaxesCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Tax
 */
class GetTaxesCommandHandler extends CommandHandler
{
    /**
     * @param GetTaxesCommand $command
     *
     * @return CommandResult
     * @throws ContainerException
     * @throws \InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetTaxesCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::TAXES)) {
            throw new AccessDeniedException('You are not allowed to read taxes.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var Collection $taxes */
        $taxes = $taxRepository->getFiltered(
            $command->getField('params'),
            $settingsService->getSetting('general', 'itemsPerPage')
        );

        /** @var Collection $taxes */
        $taxes = $taxes->length() ? $taxRepository->getWithEntities(
            ['ids' => $taxes->keys(), 'events' => true]
        ) : new Collection();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved taxes.');
        $result->setData(
            [
                Entities::TAXES => $taxes->toArray(),
                'filteredCount' => (int)$taxRepository->getCount($command->getField('params')),
                'totalCount'    => (int)$taxRepository->getCount([]),
            ]
        );

        return $result;
    }
}
