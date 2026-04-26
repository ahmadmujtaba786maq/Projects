<?php

namespace AmeliaBooking\Application\Commands\Bookable\Category;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;

/**
 * Class GetCategoriesCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class GetCategoriesCommandHandler extends CommandHandler
{
    /**
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle()
    {
        $result = new CommandResult();

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');
        /** @var BookableApplicationService $bookableService */
        $bookableService = $this->container->get('application.bookable.service');

        /**
         * Get services
         */
        $services = $serviceRepository->getAllArrayIndexedById();

        if (!$services instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get bookable.');

            return $result;
        }

        /**
         * Add services to categories
         */
        $categories = $categoryRepository->getAllIndexedById();

        if (!$categories instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get bookable.');

            return $result;
        }

        $bookableService->addServicesToCategories($categories, $services);

        $categoriesArray = $categories->toArray();

        $categoriesArray = apply_filters('amelia_get_categories_filter', $categoriesArray);

        do_action('amelia_get_categories', $categoriesArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved categories.');
        $result->setData(
            [
            Entities::CATEGORIES => $categoriesArray
            ]
        );

        return $result;
    }
}
