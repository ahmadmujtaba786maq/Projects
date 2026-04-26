<?php

namespace AmeliaBooking\Application\Commands\Bookable\Package;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;

/**
 * Class UpdatePackagesPositionsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Category
 */
class UpdatePackagesPositionsCommandHandler extends CommandHandler
{
    /**
     * @param UpdatePackagesPositionsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(UpdatePackagesPositionsCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::PACKAGES)) {
            throw new AccessDeniedException('You are not allowed to update bookable packages positions.');
        }

        $result = new CommandResult();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        $packagesPositions = [];

        foreach ($command->getFields()['packages'] as $key => $value) {
            $packagesPositions[$value['id']] = $value['position'];
        }

        /** @var Collection $packages */
        $packages = $packageRepository->getAll();

        $packageRepository->beginTransaction();

        /** @var Package $package */
        foreach ($packages->getItems() as $package) {
            $package->setPosition(
                new PositiveInteger(
                    !empty($packagesPositions[$package->getId()->getValue()]) ?
                    $packagesPositions[$package->getId()->getValue()] : count($packagesPositions)
                )
            );

            $packageRepository->updateFieldById($package->getId()->getValue(), $package->getPosition()->getValue(), 'position');
        }

        $packageRepository->commit();

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get('domain.settings.service');

        $settings = $settingsService->getAllSettingsCategorized();

        $settings['general']['sortingPackages'] = $command->getFields()['sorting'];

        $settingsService->setAllSettings($settings);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated bookable packages positions.');

        return $result;
    }
}
