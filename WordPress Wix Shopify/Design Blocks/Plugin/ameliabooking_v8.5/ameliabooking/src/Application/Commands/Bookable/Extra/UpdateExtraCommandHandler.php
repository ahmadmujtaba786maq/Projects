<?php

namespace AmeliaBooking\Application\Commands\Bookable\Extra;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ExtraFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;

/**
 * Class UpdateExtraCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Extra
 */
class UpdateExtraCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'name',
        'description',
        'price',
        'maxQuantity',
        'duration'
    ];

    /**
     * @param UpdateExtraCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateExtraCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update bookable extra');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $extra = ExtraFactory::create($command->getFields());
        if (!$extra instanceof Extra) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update Bookable extra entity.');

            return $result;
        }

        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');
        if ($extraRepository->update($command->getArg('id'), $extra)) {
            $extra->setId(new Id($command->getArg('id')));

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated bookable extra.');
            $result->setData(
                [
                Entities::EXTRA => $extra->toArray()
                ]
            );
        }

        return $result;
    }
}
