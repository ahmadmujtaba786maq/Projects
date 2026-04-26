<?php

namespace AmeliaBooking\Application\Commands\Bookable\Extra;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;

/**
 * Class GetExtraCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Extra
 */
class GetExtraCommandHandler extends CommandHandler
{
    /**
     * @param GetExtraCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetExtraCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to read bookable extra');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        $extra = $extraRepository->getById($command->getArg('id'));

        if (!$extra instanceof Extra) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get bookable extra');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved extra');
        $result->setData(
            [
            Entities::EXTRA => $extra->toArray()
            ]
        );

        return $result;
    }
}
