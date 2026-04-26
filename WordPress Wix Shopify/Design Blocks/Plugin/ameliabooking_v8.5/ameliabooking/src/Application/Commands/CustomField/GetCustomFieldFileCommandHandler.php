<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\CustomField;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;

/**
 * Class GetCustomFieldFileCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\CustomField
 */
class GetCustomFieldFileCommandHandler extends CommandHandler
{
    /**
     * @param GetCustomFieldFileCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function handle(GetCustomFieldFileCommand $command)
    {
        /** @var AbstractUser $currentUser */
        $currentUser = $this->container->get('logged.in.user');

        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');

        $isCabinetPage = $command->getPage() === 'cabinet';

        $result = new CommandResult();

        if ($currentUser === null && $isCabinetPage) {
            try {
                $currentUser = $userAS->authorization($command->getToken(), $command->getCabinetType());
            } catch (AuthorizationException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );
                return $result;
            }
        }
        if ($currentUser === null) {
            try {
                $currentUser = $userAS->authorization($command->getToken(), 'urlAttachment');
            } catch (AuthorizationException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );
                return $result;
            }
        }

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        if (
            $currentUser === null ||
            ($currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_CUSTOMER && !$isCabinetPage)
        ) {
            throw new AccessDeniedException('You are not allowed to read file.');
        }

        $customer = null;

        $customerBooking = null;

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        $customField = $customFieldRepository->getById($command->getArg('id'));

        if ($customField && $customField->getSaveType() && $customField->getSaveType()->getValue() === 'customer') {
            /** @var CustomerRepository $customerRepository */
            $customerRepository = $this->container->get('domain.users.customers.repository');

            $customer = $customerRepository->getById($command->getArg('bookingId'));
        } else {
            /** @var CustomerBookingRepository $customerBookingRepository */
            $customerBookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            /** @var CustomerBooking $customerBooking */
            $customerBooking = $customerBookingRepository->getById($command->getArg('bookingId'));
        }

        if (!$customer && $currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_PROVIDER) {
            $allowedReading = false;

            if ($customerBooking->getAppointmentId()) {
                /** @var AppointmentRepository $appointmentRepository */
                $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

                /** @var Appointment $appointment */
                $appointment = $appointmentRepository->getById($customerBooking->getAppointmentId()->getValue());

                $allowedReading = $currentUser->getId()->getValue() === $appointment->getProviderId()->getValue();
            } else {
                /** @var EventRepository $eventRepository */
                $eventRepository = $this->container->get('domain.booking.event.repository');

                /** @var Event $event */
                $event = $eventRepository->getByBookingId(
                    $customerBooking->getId()->getValue(),
                    [
                        'fetchEventsProviders' => true,
                    ]
                );

                $event->getBookings()->addItem($customerBooking, $customerBooking->getId()->getValue());

                /** @var Provider $provider */
                foreach ($event->getProviders()->getItems() as $provider) {
                    if ($currentUser->getId()->getValue() === $provider->getId()->getValue()) {
                        $allowedReading = true;
                    }
                }
            }

            if (!$allowedReading) {
                throw new AccessDeniedException('You are not allowed to read file.');
            }
        }

        $customFields = $customerBooking ?
            json_decode($customerBooking->getCustomFields()->getValue(), true) :
            json_decode($customer->getCustomFields()->getValue(), true);

        if (!isset($customFields[$command->getArg('id')]['value'][$command->getArg('index')])) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get custom field file.');

            return $result;
        }

        $result->setAttachment(true);

        $fileInfo = $customFields[$command->getArg('id')]['value'][$command->getArg('index')];

        $result->setFile(
            [
            'name'     => $fileInfo['name'],
            'type'     => AbstractCustomFieldApplicationService::$allowedUploadedFileExtensions[
                '.' . strtolower(pathinfo($fileInfo['fileName'], PATHINFO_EXTENSION))
            ],
            'content'  => file_get_contents(
                $customFieldService->getUploadsPath() . $command->getArg('bookingId') . '_' . $fileInfo['fileName']
            ),
            'size'     => filesize(
                $customFieldService->getUploadsPath() . $command->getArg('bookingId') . '_' . $fileInfo['fileName']
            )
            ]
        );

        $result->setResult(CommandResult::RESULT_SUCCESS);

        return $result;
    }
}
