<?php

namespace AmeliaBooking\Application\Commands\CustomField;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\CustomField\CustomFieldFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;

/**
 * Class UpdateCustomFieldsPositionsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\CustomField
 */
class UpdateCustomFieldsPositionsCommandHandler extends CommandHandler
{
    /**
     * @param UpdateCustomFieldsPositionsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(UpdateCustomFieldsPositionsCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::CUSTOM_FIELDS)) {
            throw new AccessDeniedException('You are not allowed to update custom fields positions.');
        }

        $result = new CommandResult();

        $customFieldsArray = $command->getFields()['customFields'];

        $categories = [];

        foreach ($customFieldsArray as $customFieldArray) {
            $customField = CustomFieldFactory::create($customFieldArray);
            if (!$customField instanceof CustomField) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not update bookable categories positions.');

                return $result;
            }

            $categories[] = $customField;
        }

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        foreach ($categories as $customField) {
            $customFieldRepository->updateFieldById($customField->getId()->getValue(), $customField->getPosition()->getValue(), 'position');
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated custom fields positions.');

        return $result;
    }
}
