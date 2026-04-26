<?php

namespace AmeliaBooking\Application\Commands\Bookable\Extra;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ExtraFactory;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;

/**
 * Class AddExtraCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Extra
 */
class AddExtraCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'name',
        'price'
    ];

    /**
     * @param AddExtraCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(AddExtraCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to add service extra');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $extra = ExtraFactory::create($command->getFields());
        if (!$extra instanceof Extra) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create service extra.');

            return $result;
        }


        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        $extraRepository->beginTransaction();

        if (!($extraId = $extraRepository->add($extra))) {
            $extraRepository->rollback();
        }

        $extra->setId(new Id($extraId));

        $extraRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new service extra.');
        $result->setData(
            [
                Entities::EXTRA => $extra->toArray(),
            ]
        );

        return $result;
    }
}
