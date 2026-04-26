<?php

namespace AmeliaBooking\Application\Commands\Bookable\Category;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\CategoryFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;

/**
 * Class UpdateCategoriesPositionsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Category
 */
class UpdateCategoriesPositionsCommandHandler extends CommandHandler
{
    /**
     * @param UpdateCategoriesPositionsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(UpdateCategoriesPositionsCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update bookable categories positions.');
        }

        $result = new CommandResult();

        /** @var array $categorized */
        $categorized = $command->getFields()['categories'];

        $categorized = apply_filters('amelia_before_category_position_updated_filter', $categorized);

        do_action('amelia_before_category_position_updated', $categorized);


        $categories = [];

        foreach ($categorized as $category) {
            $category = CategoryFactory::create($category);
            if (!$category instanceof Category) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not update bookable categories positions.');

                return $result;
            }

            $categories[] = $category;
        }


        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');
        foreach ($categories as $category) {
            $categoryRepository->update($category->getId()->getValue(), $category);
        }

        do_action('amelia_after_category_position_updated', $categorized);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated bookable categories positions.');

        return $result;
    }
}
