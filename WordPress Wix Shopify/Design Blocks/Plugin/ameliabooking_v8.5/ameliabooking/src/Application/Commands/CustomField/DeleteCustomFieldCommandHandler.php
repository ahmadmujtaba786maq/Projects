<?php

namespace AmeliaBooking\Application\Commands\CustomField;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;

/**
 * Class DeleteCustomFieldCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\CustomField
 */
class DeleteCustomFieldCommandHandler extends CommandHandler
{
    /**
     * @param DeleteCustomFieldCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeleteCustomFieldCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::CUSTOM_FIELDS)) {
            throw new AccessDeniedException('You are not allowed to delete custom field.');
        }

        $result = new CommandResult();

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        /** @var AbstractCustomFieldApplicationService $customFieldApplicationService */
        $customFieldApplicationService = $this->container->get('application.customField.service');

        $customFieldRepository->beginTransaction();

        /** @var CustomField $customField */
        $customField = $customFieldRepository->getById($command->getArg('id'));

        if (!$customField instanceof CustomField) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete custom field.');

            return $result;
        }

        do_action('amelia_before_cf_deleted', $customField->toArray());

        if (!$customFieldApplicationService->delete($customField)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete custom field.');

            $customFieldRepository->rollback();

            return $result;
        }

        $customFieldRepository->commit();

        do_action('amelia_after_cf_deleted', $customField->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted custom field.');

        return $result;
    }
}
