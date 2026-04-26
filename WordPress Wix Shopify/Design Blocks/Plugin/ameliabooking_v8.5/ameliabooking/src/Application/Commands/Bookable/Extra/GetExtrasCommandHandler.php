<?php

namespace AmeliaBooking\Application\Commands\Bookable\Extra;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;

/**
 * Class GetExtrasCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Extra
 */
class GetExtrasCommandHandler extends CommandHandler
{
    /**
     * @param GetExtrasCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetExtrasCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to read bookable extra');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        $extras = $extraRepository->getAll();

        if (!$extras instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get bookable extras');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved extras.');
        $result->setData(
            [
            Entities::EXTRAS => $extras->toArray()
            ]
        );

        return $result;
    }
}
