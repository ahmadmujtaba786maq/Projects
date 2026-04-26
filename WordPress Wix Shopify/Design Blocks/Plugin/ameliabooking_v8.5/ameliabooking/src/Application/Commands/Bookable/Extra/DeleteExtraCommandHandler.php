<?php

namespace AmeliaBooking\Application\Commands\Bookable\Extra;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;

/**
 * Class DeleteExtraCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Extra
 */
class DeleteExtraCommandHandler extends CommandHandler
{
    /**
     * @param DeleteExtraCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeleteExtraCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to delete bookable extra');
        }

        $result = new CommandResult();

        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        /** @var BookableApplicationService $bookableApplicationService */
        $bookableApplicationService = $this->getContainer()->get('application.bookable.service');

        /** @var Extra $extra */
        $extra = $extraRepository->getById($command->getArg('id'));

        if (!$extra instanceof Extra) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete a bookable extra.');

            return $result;
        }

        $extraRepository->beginTransaction();

        if (!$bookableApplicationService->deleteExtra($extra)) {
            $extraRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete a bookable extra.');

            return $result;
        }

        $extraRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted bookable extra.');
        $result->setData(
            [
            Entities::EXTRA => $extra->toArray()
            ]
        );

        return $result;
    }
}
