<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Report;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Report\AbstractReportService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class GetCustomersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Report
 */
class GetCustomersCommandHandler extends CommandHandler
{
    /**
     * @param GetCustomersCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(GetCustomersCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::CUSTOMERS)) {
            throw new AccessDeniedException('You are not allowed to read customers.');
        }

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');
        /** @var AbstractReportService $reportService */
        $reportService = $this->container->get('infrastructure.report.csv.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $params = $command->getField('params');

        if (!$command->getPermissionService()->currentUserCanReadOthers(Entities::CUSTOMERS)) {
            /** @var ProviderApplicationService $providerAS */
            $providerAS = $this->container->get('application.user.provider.service');

            /** @var AbstractUser $currentUser */
            $currentUser = $this->container->get('logged.in.user');

            /** @var Collection $providerCustomers */
            $providerCustomers = $providerAS->getAllowedCustomers($currentUser);

            $params['customers'] = array_column($providerCustomers->toArray(), 'id');
        }

        $customers = $customerRepository->getFiltered($params, null);

        $rows = [];

        $fields    = $command->getField('params')['fields'];
        $delimiter = $command->getField('params')['delimiter'];

        $dateFormat = $settingsDS->getSetting('wordpress', 'dateFormat');
        $timeFormat = $settingsDS->getSetting('wordpress', 'timeFormat');

        foreach ($customers as $customer) {
            $row = [];

            if (in_array('firstName', $fields, true)) {
                $row[BackendStrings::getUserStrings()['first_name']] = $customer['firstName'];
            }

            if (in_array('lastName', $fields, true)) {
                $row[BackendStrings::getUserStrings()['last_name']] = $customer['lastName'];
            }

            if (in_array('email', $fields, true)) {
                $row[BackendStrings::getUserStrings()['email']] = $customer['email'];
            }

            if (in_array('phone', $fields, true)) {
                $row[BackendStrings::getCommonStrings()['phone']] = $customer['phone'];
            }

            if (in_array('gender', $fields, true)) {
                $row[BackendStrings::getCustomerStrings()['gender']] = $customer['gender'];
            }

            if (in_array('birthday', $fields, true)) {
                $row[BackendStrings::getCustomerStrings()['date_of_birth']] = $customer['birthday'] ?
                    DateTimeService::getCustomDateTimeObject($customer['birthday'])
                        ->format($dateFormat) : null;
            }

            if (in_array('note', $fields, true)) {
                $row[BackendStrings::getCustomerStrings()['customer_note']] = $customer['note'];
            }

            if (in_array('lastAppointment', $fields, true)) {
                $row[BackendStrings::getCustomerStrings()['last_appointment']] =
                    DateTimeService::getCustomDateTimeObject($customer['lastAppointment'])
                        ->format($dateFormat . ' ' . $timeFormat);
            }

            if (in_array('totalAppointments', $fields, true)) {
                $row[BackendStrings::getCustomerStrings()['total_appointments']] = $customer['totalAppointments'];
            }

            if (in_array('pendingAppointments', $fields, true)) {
                $row[BackendStrings::getCustomerStrings()['pending_appointments']] =
                    $customer['countPendingAppointments'];
            }

            $row = apply_filters('amelia_before_csv_export_customers', $row, $customer);

            $rows[] = $row;
        }

        $reportService->generateReport($rows, Entities::CUSTOMERS, $delimiter);

        $result->setAttachment(true);

        return $result;
    }
}
