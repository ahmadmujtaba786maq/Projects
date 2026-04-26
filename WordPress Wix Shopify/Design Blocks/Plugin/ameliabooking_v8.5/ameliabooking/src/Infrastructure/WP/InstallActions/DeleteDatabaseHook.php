<?php

/**
 * Database hook for activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesCustomersServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesCustomersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesLocationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesProvidersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ResourcesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ResourcesToEntitiesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\CategoriesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ExtrasTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesViewsTable;
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
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Cache\CacheTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsToEventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsToPackagesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField\CustomFieldsEventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField\CustomFieldsOptionsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField\CustomFieldsServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField\CustomFieldsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Gallery\GalleriesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsToServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsViewsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsLogTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsSMSHistoryTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsTableInsertRows;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsToEntitiesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment\PaymentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Tax\TaxesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Tax\TaxesToEntitiesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersGoogleCalendarTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersLocationTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersOutlookCalendarTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersPeriodLocationTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersPeriodServiceTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersPeriodTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersSpecialDayPeriodLocationTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersSpecialDayPeriodServiceTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersSpecialDayPeriodTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersSpecialDayTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersViewsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersServiceTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersWeekDayTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersTimeOutTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersDayOffTable;

/**
 * Class DeleteDatabaseHook
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class DeleteDatabaseHook
{
    /**
     * Delete the plugin tables
     *
     * @throws InvalidArgumentException
     */
    public static function delete()
    {
        CustomFieldsEventsTable::delete();

        CustomFieldsServicesTable::delete();

        CustomFieldsOptionsTable::delete();

        CustomFieldsTable::delete();

        NotificationsSMSHistoryTable::delete();

        NotificationsLogTable::delete();

        LocationsViewsTable::delete();

        PaymentsTable::delete();

        CustomerBookingsToEventsPeriodsTable::delete();

        CustomerBookingsToExtrasTable::delete();

        CustomerBookingToEventsTicketsTable::delete();

        CustomerBookingsTable::delete();

        CouponsToEventsTable::delete();

        CouponsToPackagesTable::delete();

        EventsProvidersTable::delete();

        EventsPeriodsTable::delete();

        EventsTagsTable::delete();

        EventsTicketsTable::delete();

        EventsTable::delete();

        AppointmentsTable::delete();

        ExtrasTable::delete();

        PackagesTable::delete();

        PackagesServicesTable::delete();

        PackagesServicesProvidersTable::delete();

        PackagesServicesLocationsTable::delete();

        PackagesCustomersTable::delete();

        PackagesCustomersServicesTable::delete();

        ResourcesToEntitiesTable::delete();

        ResourcesTable::delete();

        ProvidersServiceTable::delete();

        CouponsToServicesTable::delete();

        ServicesViewsTable::delete();

        ProvidersSpecialDayPeriodServiceTable::delete();

        ProvidersSpecialDayPeriodLocationTable::delete();

        ProvidersPeriodServiceTable::delete();

        ProvidersPeriodLocationTable::delete();

        ServicesTable::delete();

        CategoriesTable::delete();

        ProvidersOutlookCalendarTable::delete();

        ProvidersGoogleCalendarTable::delete();

        ProvidersViewsTable::delete();

        ProvidersSpecialDayPeriodTable::delete();

        ProvidersPeriodTable::delete();

        ProvidersTimeOutTable::delete();

        ProvidersSpecialDayTable::delete();

        ProvidersWeekDayTable::delete();

        ProvidersLocationTable::delete();

        ProvidersDayOffTable::delete();

        NotificationsTableInsertRows::delete();

        NotificationsTable::delete();

        NotificationsToEntitiesTable::delete();

        LocationsTable::delete();

        CouponsTable::delete();

        GalleriesTable::delete();

        UsersTable::delete();

        CacheTable::delete();

        TaxesToEntitiesTable::delete();

        TaxesTable::delete();
    }
}
