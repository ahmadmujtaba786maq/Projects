<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetCustomersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class GetCustomersCommandHandler extends CommandHandler
{
    /**
     * @param GetCustomersCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     * @throws AccessDeniedException
     */
    public function handle(GetCustomersCommand $command)
    {
        $result = new CommandResult();

        /** @var AbstractUser $currentUser */
        $currentUser = $this->container->get('logged.in.user');

        if (
            !$command->getPermissionService()->currentUserCanRead(Entities::CUSTOMERS) &&
            !($currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_PROVIDER)
        ) {
            if ($command->getToken()) {
                /** @var UserApplicationService $userAS */
                $userAS = $this->container->get('application.user.service');

                try {
                    $currentUser = $userAS->authorization($command->getToken(), 'provider');
                } catch (AuthorizationException $e) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setData(
                        [
                            'reauthorize' => true
                        ]
                    );

                    return $result;
                }
            } else {
                throw new AccessDeniedException('You are not allowed to read customers.');
            }
        }

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->getContainer()->get('domain.users.customers.repository');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var ProviderApplicationService $providerAS */
        $providerAS = $this->container->get('application.user.provider.service');

        $params = $command->getField('params');

        $countParams = [];

        if (
            empty($params['customers']) &&
            !$command->getPermissionService()->currentUserCanReadOthers(Entities::CUSTOMERS)
        ) {
            /** @var Collection $providerCustomers */
            $providerCustomers = $providerAS->getAllowedCustomers($currentUser);

            $params['customers'] = array_column($providerCustomers->toArray(), 'id');

            $countParams['customers'] = $params['customers'];
        }

        $itemsPerPage = !empty($params['limit']) ?
            $params['limit'] : $settingsService->getSetting('general', 'itemsPerPageBackEnd');

        $users = $customerRepository->getFiltered(
            array_merge($params, ['ignoredBookings' => empty($params['noShow'])]),
            $itemsPerPage
        );

        if (!empty($users)) {
            $usersWithBookingsStats = $customerRepository->getFiltered(
                array_merge($params, ['ignoredBookings' => false, 'customers' => array_keys($users)]),
                null
            );

            foreach ($users as $key => $user) {
                $users[$key] = $usersWithBookingsStats[$key];
            }
        }

        $customersNoShowCount = [];

        $noShowTagEnabled = $settingsService->getSetting('roles', 'enableNoShowTag');

        if ($noShowTagEnabled && $users) {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            $usersIds = array_map(
                function ($user) {
                    return $user['id'];
                },
                $users
            );

            $customersNoShowCount =  $usersIds ? $bookingRepository->countByNoShowStatus($usersIds) : [];
        }

        $users = array_values($users);

        foreach ($users as $key => &$user) {
            $user['wpUserPhotoUrl'] = $this->container->get('user.avatar')->getAvatar($user['externalId']);

            if ($noShowTagEnabled) {
                $user['noShowCount'] = $customersNoShowCount[$key]['count'];
            }

            $user = array_map(
                function ($v) {
                    return (null === $v) ? '' : $v;
                },
                $user
            );
        }

        $users = apply_filters('amelia_get_customers_filter', $users);

        do_action('amelia_get_customers', $users);

        $resultData = [
            Entities::USER . 's' => $users,
        ];

        if (empty($params['skipCount'])) {
            $resultData['filteredCount'] = (int)$customerRepository->getCount($params);

            $resultData['totalCount'] = (int)$customerRepository->getCount($countParams);
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved users.');
        $result->setData($resultData);

        return $result;
    }
}
