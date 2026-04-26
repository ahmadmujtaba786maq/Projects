<?php

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateServicesPositionsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Category
 */
class UpdateServicesPositionsCommandHandler extends CommandHandler
{
    /**
     * @param UpdateServicesPositionsCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(UpdateServicesPositionsCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update bookable services positions.');
        }

        $result = new CommandResult();

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var Collection $services */
        $services = $serviceRepository->getFiltered(['sort' => $command->getFields()['sorting']]);

        $servicesArray = $services->toArray();

        if (
            $command->getFields()['sorting'] === 'custom' &&
            $customSortedServicesArray = $command->getFields()['services']
        ) {
            $customSortedServicesIds = array_column($customSortedServicesArray, 'id');

            $sortedServicesArray = [];

            foreach ($servicesArray as $serviceArray) {
                if (in_array($serviceArray['id'], $customSortedServicesIds, false)) {
                    $sortedServicesArray[] = null;
                } else {
                    $sortedServicesArray[] = $serviceArray;
                }
            }

            foreach ($sortedServicesArray as $index => $serviceArray) {
                if ($serviceArray === null) {
                    $sortedServicesArray[$index] = array_shift($customSortedServicesArray);
                }
            }

            $servicesArray = $sortedServicesArray;
        }

        $serviceRepository->beginTransaction();

        $servicesArray = apply_filters('amelia_before_service_position_updated_filter', $servicesArray);

        do_action('amelia_before_service_position_updated', $servicesArray);

        foreach ($servicesArray as $index => $serviceArray) {
            $serviceRepository->updateFieldById($serviceArray['id'], $index + 1, 'position');
        }

        $serviceRepository->commit();

        do_action('amelia_after_service_position_updated', $servicesArray);

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get('domain.settings.service');

        $settings = $settingsService->getAllSettingsCategorized();

        $settings['general']['sortingServices'] = $command->getFields()['sorting'];

        $settingsService->setAllSettings($settings);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated bookable services positions.');

        return $result;
    }
}
