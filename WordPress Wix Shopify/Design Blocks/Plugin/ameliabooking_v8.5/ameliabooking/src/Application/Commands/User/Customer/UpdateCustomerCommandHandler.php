<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\Password;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\Services\Mailchimp\AbstractMailchimpService;
use Interop\Container\Exception\ContainerException;

/**
 * Class UpdateCustomerCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class UpdateCustomerCommandHandler extends CommandHandler
{
    /**
     * @param UpdateCustomerCommand $command
     *
     * @return CommandResult
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     */
    public function handle(UpdateCustomerCommand $command)
    {
        /** @var CommandResult $result */
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('domain.users.repository');

        /** @var AbstractMailchimpService $mailchimpService */
        $mailchimpService = $this->container->get('infrastructure.mailchimp.service');

        $userRepository->beginTransaction();

        $customerData = $command->getFields();

        if (!$command->getPermissionService()->currentUserCanWrite(Entities::CUSTOMERS)) {
            if ($command->getToken()) {
                /** @var AbstractUser $provider */
                $provider = $command->getCabinetType() === 'provider'
                    ? $userAS->getAuthenticatedUser($command->getToken(), false, 'providerCabinet')
                    : null;

                $oldUser = $provider === null
                    ? $userAS->getAuthenticatedUser($command->getToken(), false, 'customerCabinet')
                    : $userRepository->getById($customerData['id']);

                if (
                    $provider === null &&
                    ($oldUser === null || $oldUser->getId()->getValue() !== intval($command->getArg('id')))
                ) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage('Could not retrieve user');
                    $result->setData(
                        [
                            'reauthorize' => true
                        ]
                    );

                    return $result;
                }
            } else {
                throw new AccessDeniedException('You are not allowed to perform this action!');
            }
        } else {
            $oldUser = $userRepository->getById($command->getArg('id'));
        }

        if ($command->getField('externalId') === -1) {
            $command->setField('externalId', null);
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var AbstractUser $currentUser */
        $currentUser = $this->container->get('logged.in.user');

        if (
            $command->getField('email') === '' &&
            !$settingsService->getSetting('roles', 'allowCustomerDeleteProfile') &&
            (!$currentUser || $currentUser->getType() === AbstractUser::USER_ROLE_CUSTOMER)
        ) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update user.');

            return $result;
        }

        if (!isset($customerData['password'])) {
            $customerData['translations'] = !empty($customerData['translations']) ? $customerData['translations'] : null;

            $customerData['birthday'] = !empty($customerData['birthday']) ? $customerData['birthday'] : null;
        }

        $newUserData = array_merge($oldUser->toArray(), $customerData);

        $newUserData = apply_filters('amelia_before_customer_updated_filter', $newUserData);

        /** @var Customer $newUser */
        $newUser = UserFactory::create($newUserData);

        if (
            $userRepository->getByEmail($newUser->getEmail()->getValue()) &&
            $oldUser->getEmail()->getValue() !== $newUser->getEmail()->getValue()
        ) {
            $result->setResult(CommandResult::RESULT_CONFLICT);
            $result->setMessage('Email already exist.');
            $result->setData('This email is already in use.');

            return $result;
        }

        if ($command->getField('password')) {
            /** @var Password $newPassword */
            $newPassword = new Password($command->getField('password'));

            $userRepository->updateFieldById($command->getArg('id'), $newPassword->getValue(), 'password');

            if ($newUser->getExternalId() && $newUser->getExternalId()->getValue()) {
                add_filter('amelia_user_profile_updated', '__return_true');
                wp_set_password($command->getField('password'), $newUser->getExternalId()->getValue());
                remove_filter('amelia_user_profile_updated', '__return_true');
            }
        }

        do_action('amelia_before_customer_updated', $newUser ? $newUser->toArray() : null);

        if (!$userRepository->update($command->getArg('id'), $newUser)) {
            $userRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update user.');

            return $result;
        }

        if ($command->getField('externalId') === 0) {
            /** @var UserApplicationService $userAS */
            $userAS = $this->getContainer()->get('application.user.service');

            $userAS->setWpUserIdForNewUser($command->getArg('id'), $newUser);
        } elseif ($newUser->getExternalId() && $newUser->getExternalId()->getValue()) {
            add_filter('amelia_user_profile_updated', '__return_true');
            wp_update_user(
                [
                    'ID' => $newUser->getExternalId()->getValue(),
                    'first_name' => $newUser->getFirstName() ? $newUser->getFirstName()->getValue() : '',
                    'last_name'  => $newUser->getLastName() ? $newUser->getLastName()->getValue() : '',
                    'user_email' => $newUser->getEmail() ? $newUser->getEmail()->getValue() : ''
                ]
            );

            if ($uid = get_current_user_id()) {
                clean_user_cache($uid);
            }

            remove_filter('amelia_user_profile_updated', '__return_true');
        }

        if ($command->getField('email') === '') {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            $bookingRepository->updateInfoByCustomerId($oldUser->getId()->getValue(), null);
        }

        if ($oldUser->getEmail() && $oldUser->getEmail()->getValue() && $newUser->getEmail() && $newUser->getEmail()->getValue()) {
            $mailchimpService->addOrUpdateSubscriber($oldUser->getEmail()->getValue(), $newUser->toArray(), false);
        }

        $userRepository->commit();

        do_action('amelia_after_customer_updated', $newUser ? $newUser->toArray() : null);

        $result = $userAS->getAuthenticatedUserResponse(
            $newUser,
            $oldUser->getEmail()->getValue() !== $newUser->getEmail()->getValue(),
            true,
            $oldUser->getLoginType(),
            'customer'
        );

        $result->setMessage('Successfully updated user');

        return $result;
    }
}
