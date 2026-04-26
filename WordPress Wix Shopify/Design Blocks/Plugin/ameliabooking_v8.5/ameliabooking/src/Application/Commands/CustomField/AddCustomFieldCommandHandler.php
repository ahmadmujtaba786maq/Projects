<?php

namespace AmeliaBooking\Application\Commands\CustomField;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\CustomField\CustomFieldFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldServiceRepository;

/**
 * Class AddCustomFieldCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\CustomField
 */
class AddCustomFieldCommandHandler extends CommandHandler
{
    /**
     * @param AddCustomFieldCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     */
    public function handle(AddCustomFieldCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::CUSTOM_FIELDS)) {
            throw new AccessDeniedException('You are not allowed to add custom fields.');
        }

        $result = new CommandResult();

        $customFieldArray = $command->getFields()['customField'];

        $customFieldArray = apply_filters('amelia_before_cf_added_filter', $customFieldArray);

        do_action('amelia_before_cf_added', $customFieldArray);

        $customField = CustomFieldFactory::create($customFieldArray);

        if (!$customField instanceof CustomField) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not add custom fields.');

            return $result;
        }

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        $customFieldRepository->beginTransaction();

        try {
            if (!($customFieldId = $customFieldRepository->add($customField))) {
                $customFieldRepository->rollback();
                return $result;
            }

            $customField->setId(new Id($customFieldId));

            $this->handleCustomFieldServices($customField);
        } catch (QueryExecutionException $e) {
            $customFieldRepository->rollback();
            throw $e;
        }

        $customFieldRepository->commit();

        do_action('amelia_after_cf_added', $customField->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new custom field.');
        $result->setData(
            [
            'customField' => $customField->toArray()
            ]
        );

        return $result;
    }

    /**
     * @param CustomField $customField
     *
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function handleCustomFieldServices($customField)
    {
        /** @var CustomFieldServiceRepository $customFieldServiceRepository */
        $customFieldServiceRepository = $this->container->get('domain.customFieldService.repository');

        /** @var Service $service */
        foreach ($customField->getServices()->getItems() as $service) {
            $customFieldServiceRepository->add($customField->getId()->getValue(), $service->getId()->getValue());
        }
    }
}
