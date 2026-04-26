<?php

namespace AmeliaBooking\Application\Commands\Bookable\Category;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\CategoryFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\CategoryRepository;

/**
 * Class UpdateCategoryCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Category
 */
class UpdateCategoryCommandHandler extends CommandHandler
{
    /**
     * @param UpdateCategoryCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(UpdateCategoryCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update bookable category.');
        }

        $result = new CommandResult();

        $categoryArray = $command->getFields();

        $categoryArray = apply_filters('amelia_before_category_updated_filter', $categoryArray);

        do_action('amelia_before_category_updated', $categoryArray);

        $category = CategoryFactory::create($categoryArray);
        if (!$category instanceof Category) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update bookable category.');

            return $result;
        }

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get('domain.bookable.category.repository');
        if ($categoryRepository->update($command->getArg('id'), $category)) {
            $category->setId(new Id($command->getArg('id')));

            do_action('amelia_after_category_updated', $category->toArray());

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated bookable category.');
            $result->setData(
                [
                Entities::CATEGORY => $category->toArray()
                ]
            );
        }

        return $result;
    }
}
