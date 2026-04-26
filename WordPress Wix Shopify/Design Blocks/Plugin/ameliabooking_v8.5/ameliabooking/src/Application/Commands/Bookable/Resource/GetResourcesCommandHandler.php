<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Resource;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Resource\AbstractResourceApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetResourcesCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Resource
 */
class GetResourcesCommandHandler extends CommandHandler
{
    /**
     * @param GetResourcesCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(GetResourcesCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::RESOURCES)) {
            throw new AccessDeniedException('You are not allowed to read resources.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $params = $command->getField('params');

        /** @var AbstractResourceApplicationService $resourceApplicationService */
        $resourceApplicationService = $this->container->get('application.resource.service');

        /** @var Collection $resources */
        $resources = $resourceApplicationService->getAll(['search' => $params['search']]);

        $resourcesArray = $resources->toArray();

        $resourcesArray = apply_filters('amelia_get_resources_filter', $resourcesArray);

        do_action('amelia_get_resources', $resourcesArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved resources.');
        $result->setData(
            [
                Entities::RESOURCES => $resourcesArray,
            ]
        );

        return $result;
    }
}
