<?php

namespace AmeliaBooking\Application\Commands\Tax;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxRepository;

/**
 * Class UpdateTaxStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Tax
 */
class UpdateTaxStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdateTaxStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     */
    public function handle(UpdateTaxStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::TAXES)) {
            throw new AccessDeniedException('You are not allowed to update tax!');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->getContainer()->get('domain.tax.repository');

        $taxRepository->updateStatusById(
            $command->getArg('id'),
            $command->getField('status')
        );

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated tax');
        $result->setData(true);

        return $result;
    }
}
