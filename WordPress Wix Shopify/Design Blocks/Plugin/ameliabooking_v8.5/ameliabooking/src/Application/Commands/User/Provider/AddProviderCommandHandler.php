<?php

namespace AmeliaBooking\Application\Commands\User\Provider;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Password;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddProviderCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Provider
 */
class AddProviderCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'type',
        'firstName',
        'lastName',
        'email'
    ];

    /**
     * @param AddProviderCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function handle(AddProviderCommand $command)
    {
        if (
            !$command->getPermissionService()->currentUserCanWrite(Entities::EMPLOYEES) ||
            !$command->getPermissionService()->currentUserCanWriteOthers(Entities::EMPLOYEES)
        ) {
            throw new AccessDeniedException('You are not allowed to add employee.');
        }

        /** @var ProviderApplicationService $providerAS */
        $providerAS = $this->container->get('application.user.provider.service');

        $this->checkMandatoryFields($command);

        $providerData = $command->getFields();

        $providerData = apply_filters('amelia_before_provider_added_filter', $providerData);

        do_action('amelia_before_provider_added', $providerData);

        $result = $providerAS->createProvider($providerData);

        do_action('amelia_after_provider_added', $result ? $result->getData() : null);

        return $result;
    }
}
