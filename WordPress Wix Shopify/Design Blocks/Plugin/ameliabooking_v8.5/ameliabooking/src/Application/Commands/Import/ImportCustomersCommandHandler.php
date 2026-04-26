<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Import;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class ImportCustomersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Import
 */
class ImportCustomersCommandHandler extends CommandHandler
{
    /**
     * @param ImportCustomersCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(ImportCustomersCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::CUSTOMERS)) {
            throw new AccessDeniedException('You are not allowed to read customers.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $data = $command->getField('data');
        $num  = $command->getField('number');

        $overwriteUsers = $command->getField('overwrite');

        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('domain.users.repository');
        /** @var CustomerApplicationService $customerApplicationService */
        $customerApplicationService = $this->container->get('application.user.customer.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $dateFormat = $settingsDS->getSetting('wordpress', 'dateFormat');

        $addedUsers  = [];
        $failedUsers = [];
        $userExists  = [];

        $userRepository->beginTransaction();

        if ($overwriteUsers) {
            foreach ($overwriteUsers as $overwriteUser) {
                try {
                    if (!empty($overwriteUser['birthday']) && !empty($overwriteUser['birthday']['date'])) {
                        $overwriteUser['birthday'] = (new \DateTime($overwriteUser['birthday']['date']))->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    $failedUsers[] = $overwriteUser;
                    continue;
                }
                $removeKeys = array('index', 'id', 'externalId', 'status');
                foreach (array_keys($overwriteUser) as $key) {
                    if (in_array($key, $removeKeys) || empty($overwriteUser[$key])) {
                        unset($overwriteUser[$key]);
                    }
                }
                $email = $overwriteUser['email'] ;
                unset($overwriteUser['email']);
                if (!$userRepository->updateFieldsByEmail($email, $overwriteUser)) {
                    $failedUsers[] = $overwriteUser;
                    continue;
                }
                $addedUsers[] = $overwriteUser;
            }
            $userRepository->commit();

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully overwritten users');
            $result->setData(
                [
                    'addedUsers'  => $addedUsers,
                    'failedToAdd' => $failedUsers,
                    'existsUsers' => []
                ]
            );

            return $result;
        }

        $existingEmails = $userRepository->getAllEmailsByType('customer');

        for ($i = 0; $i < $num; $i++) {
            try {
                $customerData = [
                    'firstName' => $data['firstName'][$i],
                    'lastName'  => $data['lastName'][$i],
                    'email'     => $data['email'][$i],
                    'phone'     => !empty($data['phone']) ? $data['phone'][$i] : null,
                    'gender'    => !empty($data['gender']) ? $data['gender'][$i] : null,
                    'note'      => !empty($data['note']) ? $data['note'][$i] : null,
                    'type'      => Entities::CUSTOMER
                ];

                if ($customerData['email']) {
                    $customerData['email'] = preg_replace('/\s+/', '', $customerData['email']);
                }

                if (
                    in_array($customerData['email'], array_column($userExists, 'email')) ||
                    in_array($customerData['email'], array_column($addedUsers, 'email'))
                ) {
                    $failedUsers[] = array_merge($customerData, ['index' => $i]);
                    continue;
                }

                if (!empty($data['birthday']) && $data['birthday'][$i]) {
                    $d = \DateTime::createFromFormat($dateFormat, $data['birthday'][$i]);
                    if ($d) {
                        $customerData['birthday'] = $d->format('Y-m-d');
                    } else {
                        throw new Exception();
                    }
                }

                /** @var Customer $newUser */
                $newUser = UserFactory::create($customerData);
            } catch (Exception $e) {
                $failedUsers[] = array_merge($customerData, ['index' => $i]);
                continue;
            }

            if (!($newUser instanceof AbstractUser) || empty($newUser->getFirstName()->getValue()) || empty($newUser->getLastName()->getValue())) {
                $failedUsers[] = array_merge($customerData, ['index' => $i]);
                continue;
            }

            if ($newUser->getEmail() && $newUser->getEmail()->getValue() && in_array($newUser->getEmail()->getValue(), $existingEmails)) {
                $userExists[] = array_merge($newUser->toArray(), ['index' => $i]);
                continue;
            }

            try {
                if (!$id = $userRepository->add($newUser)) {
                    $failedUsers[] = array_merge($newUser->toArray(), ['index' => $i]);
                    continue;
                }
            } catch (QueryExecutionException $e) {
                $failedUsers[] = array_merge($newUser->toArray(), ['index' => $i]);
                continue;
            }

            $newUser->setId(new Id($id));
            $customerApplicationService->setWPUserForCustomer($newUser, true);

            $addedUsers[] = $newUser;
        }

        $userRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully imported users');
        $result->setData(
            [
                'addedUsers'  => $addedUsers,
                'failedToAdd' => $failedUsers,
                'existsUsers' => $userExists
            ]
        );

        return $result;
    }
}
