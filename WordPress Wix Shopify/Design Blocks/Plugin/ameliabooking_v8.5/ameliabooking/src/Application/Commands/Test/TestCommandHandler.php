<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Test;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\CategoriesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ExtrasTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesCustomersServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesCustomersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesLocationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesProvidersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ResourcesToEntitiesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\AppointmentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsToEventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsToExtrasTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingToEventsTicketsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsProvidersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTagsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTicketsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsToEventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsToPackagesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsToServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField\CustomFieldsEventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField\CustomFieldsServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField\CustomFieldsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsToEntitiesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment\PaymentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersLocationTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersPeriodLocationTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersPeriodServiceTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersPeriodTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersServiceTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersSpecialDayPeriodLocationTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersSpecialDayPeriodTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;
use Interop\Container\Exception\ContainerException;

/**
 * Class TestCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Test
 */
class TestCommandHandler extends CommandHandler
{
    /**
     * @param TestCommand $command
     *
     * @return CommandResult
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function handle(TestCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $items = [
            AppointmentsTable::getTableName() => [
                'serviceId'  => ServicesTable::getTableName(),
                'providerId' => UsersTable::getTableName(),
                'locationId' => LocationsTable::getTableName(),
                'parentId'   => AppointmentsTable::getTableName(),
            ],
            CouponsToEventsTable::getTableName() => [
                'couponId' => CouponsTable::getTableName(),
                'eventId'  => EventsTable::getTableName(),
            ],
            CouponsToPackagesTable::getTableName() => [
                'couponId'  => CouponsTable::getTableName(),
                'packageId' => PackagesTable::getTableName(),
            ],
            CouponsToServicesTable::getTableName() => [
                'couponId'  => CouponsTable::getTableName(),
                'serviceId' => ServicesTable::getTableName(),
            ],
            CustomerBookingsTable::getTableName() => [
                'appointmentId' => AppointmentsTable::getTableName(),
                'customerId'    => UsersTable::getTableName(),
                'couponId'      => CouponsTable::getTableName(),
            ],
            CustomerBookingsToEventsPeriodsTable::getTableName() => [
                'customerBookingId' => CustomerBookingsTable::getTableName(),
                'eventPeriodId'     => EventsPeriodsTable::getTableName(),
            ],
            CustomerBookingToEventsTicketsTable::getTableName() => [
                'customerBookingId' => CustomerBookingsTable::getTableName(),
                'eventTicketId'     => EventsTicketsTable::getTableName(),
            ],
            CustomerBookingsToExtrasTable::getTableName() => [
                'customerBookingId' => CustomerBookingsTable::getTableName(),
                'extraId'           => ExtrasTable::getTableName(),
            ],
            CustomFieldsEventsTable::getTableName() => [
                'customFieldId' => CustomFieldsTable::getTableName(),
                'eventId'       => EventsTable::getTableName(),
            ],
            CustomFieldsServicesTable::getTableName() => [
                'customFieldId' => CustomFieldsTable::getTableName(),
                'serviceId'     => ServicesTable::getTableName(),
            ],
            EventsTable::getTableName() => [
                'parentId'    => EventsTable::getTableName(),
                'locationId'  => LocationsTable::getTableName(),
                'organizerId' => UsersTable::getTableName(),
            ],
            EventsPeriodsTable::getTableName() => [
                'eventId' => EventsTable::getTableName(),
            ],
            EventsTagsTable::getTableName() => [
                'eventId' => EventsTable::getTableName(),
            ],
            EventsProvidersTable::getTableName() => [
                'eventId' => EventsTable::getTableName(),
                'userId'  => UsersTable::getTableName(),
            ],
            EventsTicketsTable::getTableName() => [
                'eventId' => EventsTable::getTableName(),
            ],
            ExtrasTable::getTableName() => [
                'serviceId' => ServicesTable::getTableName(),
            ],
            NotificationsToEntitiesTable::getTableName() => [
                'entityId/entity/appointment' => ServicesTable::getTableName(),
                'entityId/entity/event'       => EventsTable::getTableName(),
            ],
            PackagesCustomersServicesTable::getTableName() => [
                'packageCustomerId' => PackagesCustomersTable::getTableName(),
                'serviceId'         => ServicesTable::getTableName(),
                'providerId'        => UsersTable::getTableName(),
                'locationId'        => LocationsTable::getTableName(),
            ],
            PackagesServicesLocationsTable::getTableName() => [
                'packageServiceId' => PackagesServicesTable::getTableName(),
                'locationId'       => LocationsTable::getTableName(),
            ],
            PackagesServicesProvidersTable::getTableName() => [
                'packageServiceId' => PackagesServicesTable::getTableName(),
                'userId'           => UsersTable::getTableName(),
            ],
            PackagesCustomersTable::getTableName() => [
                'packageId'  => PackagesTable::getTableName(),
                'customerId' => UsersTable::getTableName(),
                'couponId'   => CouponsTable::getTableName(),
            ],
            PackagesServicesTable::getTableName() => [
                'serviceId' => ServicesTable::getTableName(),
                'packageId' => PackagesTable::getTableName(),
            ],
            PaymentsTable::getTableName() => [
                'customerBookingId' => CustomerBookingsTable::getTableName(),
                'packageCustomerId' => PackagesCustomersTable::getTableName(),
                'parentId'          => PaymentsTable::getTableName(),
            ],
            ProvidersLocationTable::getTableName() => [
                'locationId' => LocationsTable::getTableName(),
            ],
            ProvidersPeriodTable::getTableName() => [
                'locationId' => LocationsTable::getTableName(),
            ],
            ProvidersPeriodLocationTable::getTableName() => [
                'locationId' => LocationsTable::getTableName(),
            ],
            ProvidersPeriodServiceTable::getTableName() => [
                'serviceId' => ServicesTable::getTableName(),
            ],
            ProvidersServiceTable::getTableName() => [
                'userId'    => UsersTable::getTableName(),
                'serviceId' => ServicesTable::getTableName(),
            ],
            ProvidersSpecialDayPeriodTable::getTableName() => [
                'locationId' => LocationsTable::getTableName(),
            ],
            ProvidersSpecialDayPeriodLocationTable::getTableName() => [
                'locationId' => LocationsTable::getTableName(),
            ],
            ServicesTable::getTableName() => [
                'categoryId' => CategoriesTable::getTableName(),
            ],
            ResourcesToEntitiesTable::getTableName() => [
                'entityId/entityType/service'  => ServicesTable::getTableName(),
                'entityId/entityType/employee' => UsersTable::getTableName(),
                'entityId/entityType/location' => LocationsTable::getTableName(),
            ],
        ];

        $messages = [];

        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('domain.users.repository');

        foreach ($items as $testTableName => $testTableData) {
            foreach ($testTableData as $testColumnName => $targetTableName) {
                $columnData = explode('/', $testColumnName);

                $message = $userRepository->getMissingData(
                    $testTableName,
                    $columnData[0],
                    $targetTableName,
                    'id',
                    sizeof($columnData) === 1 ? null : $columnData[1],
                    sizeof($columnData) === 1 ? null : $columnData[2]
                );

                if ($message) {
                    $messages[] = $message;
                }
            }
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully checked data');
        $result->setData(
            [
                'messages' => $messages,
            ]
        );

        return $result;
    }
}
