<?php

namespace AmeliaBooking\Application\Commands\Bookable\Category;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;

/**
 * Class GetCategoryCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Category
 */
class GetCategoryCommandHandler extends CommandHandler
{
    /**
     * @param GetCategoryCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetCategoryCommand $command)
    {
        $result = new CommandResult();

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /**
         * Get services for category
         */
        $services = $serviceRepository->getByCriteria(['categories' => [$command->getArg('id')]]);

        if (!$services instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get services.');

            return $result;
        }

        /**
         * Get category
         */
        $category = $categoryRepository->getById($command->getArg('id'));

        if (!$category instanceof Category) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get category.');

            return $result;
        }

        /**
         * Add services to category
         */
        $category->setServiceList(new Collection());

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $category->getServiceList()->addItem($service, $service->getId()->getValue());
        }

        $categoryArray = $category->toArray();

        $categoryArray = apply_filters('amelia_get_category_filter', $categoryArray);

        do_action('amelia_get_category', $categoryArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved category.');
        $result->setData(
            [
            Entities::CATEGORY => $categoryArray
            ]
        );

        return $result;
    }
}
