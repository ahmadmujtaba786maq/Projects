<?php

/**
 * Assembling repositories:
 * Instantiating infrastructure-layer repositories implementing the Domain layer interfaces
 */

use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB;
use AmeliaBooking\Infrastructure\Repository;

defined('ABSPATH') or die('No script kiddies please!');

$entries['domain.bookable.category.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\CategoryRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\CategoriesTable::getTableName()
    );
};

$entries['domain.bookable.extra.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\ExtraRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\ExtrasTable::getTableName()
    );
};

$entries['domain.bookable.service.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\ServiceRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\ServicesTable::getTableName(),
        DB\User\Provider\ProvidersServiceTable::getTableName(),
        DB\Bookable\ExtrasTable::getTableName(),
        DB\Bookable\ServicesViewsTable::getTableName(),
        DB\Gallery\GalleriesTable::getTableName()
    );
};

$entries['domain.bookable.package.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\PackageRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\PackagesTable::getTableName()
    );
};

$entries['domain.bookable.package.packageService.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\PackageServiceRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\PackagesServicesTable::getTableName()
    );
};

$entries['domain.bookable.package.packageServiceLocation.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\PackageServiceLocationRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\PackagesServicesLocationsTable::getTableName()
    );
};

$entries['domain.bookable.package.packageServiceProvider.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\PackageServiceProviderRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\PackagesServicesProvidersTable::getTableName()
    );
};

$entries['domain.bookable.packageCustomer.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\PackageCustomerRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\PackagesCustomersTable::getTableName()
    );
};

$entries['domain.bookable.packageCustomerService.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\PackageCustomerServiceRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\PackagesCustomersServicesTable::getTableName()
    );
};

$entries['domain.bookable.resource.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\ResourceRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\ResourcesTable::getTableName()
    );
};

$entries['domain.bookable.resourceEntities.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\ResourceEntitiesRepository(
        $c->getDatabaseConnection(),
        DB\Bookable\ResourcesToEntitiesTable::getTableName()
    );
};

$entries['domain.tax.repository'] = function (Container $c) {
    return new Repository\Tax\TaxRepository(
        $c->getDatabaseConnection(),
        DB\Tax\TaxesTable::getTableName()
    );
};

$entries['domain.tax.entity.repository'] = function (Container $c) {
    return new Repository\Tax\TaxEntityRepository(
        $c->getDatabaseConnection(),
        DB\Tax\TaxesToEntitiesTable::getTableName()
    );
};

$entries['domain.coupon.repository'] = function (Container $c) {
    return new Repository\Coupon\CouponRepository(
        $c->getDatabaseConnection(),
        DB\Coupon\CouponsTable::getTableName(),
        DB\Bookable\ServicesTable::getTableName(),
        DB\Coupon\CouponsToServicesTable::getTableName(),
        DB\Booking\EventsTable::getTableName(),
        DB\Coupon\CouponsToEventsTable::getTableName(),
        DB\Bookable\PackagesTable::getTableName(),
        DB\Coupon\CouponsToPackagesTable::getTableName(),
        DB\Booking\CustomerBookingsTable::getTableName()
    );
};

$entries['domain.coupon.service.repository'] = function (Container $c) {
    return new Repository\Coupon\CouponServiceRepository(
        $c->getDatabaseConnection(),
        DB\Coupon\CouponsToServicesTable::getTableName()
    );
};

$entries['domain.coupon.event.repository'] = function (Container $c) {
    return new Repository\Coupon\CouponEventRepository(
        $c->getDatabaseConnection(),
        DB\Coupon\CouponsToEventsTable::getTableName()
    );
};

$entries['domain.coupon.package.repository'] = function (Container $c) {
    return new Repository\Coupon\CouponPackageRepository(
        $c->getDatabaseConnection(),
        DB\Coupon\CouponsToPackagesTable::getTableName()
    );
};

$entries['domain.locations.repository'] = function (Container $c) {
    return new Repository\Location\LocationRepository(
        $c->getDatabaseConnection(),
        DB\Location\LocationsTable::getTableName(),
        DB\User\Provider\ProvidersLocationTable::getTableName(),
        DB\User\Provider\ProvidersServiceTable::getTableName(),
        DB\Bookable\ServicesTable::getTableName(),
        DB\Location\LocationsViewsTable::getTableName()
    );
};

$entries['domain.notification.repository'] = function (Container $c) {
    return new Repository\Notification\NotificationRepository(
        $c->getDatabaseConnection(),
        DB\Notification\NotificationsTable::getTableName()
    );
};

$entries['domain.notificationEntities.repository'] = function (Container $c) {
    return new Repository\Notification\NotificationsToEntitiesRepository(
        $c->getDatabaseConnection(),
        DB\Notification\NotificationsToEntitiesTable::getTableName()
    );
};

$entries['domain.notificationLog.repository'] = function (Container $c) {
    return new Repository\Notification\NotificationLogRepository(
        $c->getDatabaseConnection(),
        DB\Notification\NotificationsLogTable::getTableName(),
        DB\Notification\NotificationsTable::getTableName(),
        DB\Booking\AppointmentsTable::getTableName(),
        DB\Booking\CustomerBookingsTable::getTableName(),
        DB\User\UsersTable::getTableName()
    );
};

$entries['domain.notificationSMSHistory.repository'] = function (Container $c) {
    return new Repository\Notification\NotificationSMSHistoryRepository(
        $c->getDatabaseConnection(),
        DB\Notification\NotificationsSMSHistoryTable::getTableName()
    );
};

$entries['domain.payment.repository'] = function (Container $c) {
    return new Repository\Payment\PaymentRepository(
        $c->getDatabaseConnection(),
        DB\Payment\PaymentsTable::getTableName(),
        DB\Booking\AppointmentsTable::getTableName(),
        DB\Booking\CustomerBookingsTable::getTableName(),
        DB\Bookable\ServicesTable::getTableName(),
        DB\User\UsersTable::getTableName(),
        DB\Booking\EventsTable::getTableName(),
        DB\Booking\EventsProvidersTable::getTableName(),
        DB\Booking\EventsPeriodsTable::getTableName(),
        DB\Booking\CustomerBookingsToEventsPeriodsTable::getTableName(),
        DB\Bookable\PackagesTable::getTableName(),
        DB\Bookable\PackagesCustomersTable::getTableName()
    );
};

$entries['domain.users.repository'] = function (Container $c) {
    return new Repository\User\UserRepository(
        $c->getDatabaseConnection(),
        DB\User\UsersTable::getTableName()
    );
};

$entries['domain.bookable.service.providerService.repository'] = function (Container $c) {
    return new Repository\Bookable\Service\ProviderServiceRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersServiceTable::getTableName()
    );
};

$entries['domain.bookable.service.providerLocation.repository'] = function (Container $c) {
    return new Repository\Location\ProviderLocationRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersLocationTable::getTableName()
    );
};

$entries['domain.schedule.dayOff.repository'] = function (Container $c) {
    return new Repository\Schedule\DayOffRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersDayOffTable::getTableName()
    );
};

$entries['domain.schedule.weekDay.repository'] = function (Container $c) {
    return new Repository\Schedule\WeekDayRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersWeekDayTable::getTableName()
    );
};

$entries['domain.schedule.timeOut.repository'] = function (Container $c) {
    return new Repository\Schedule\TimeOutRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersTimeOutTable::getTableName()
    );
};

$entries['domain.schedule.period.repository'] = function (Container $c) {
    return new Repository\Schedule\PeriodRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersPeriodTable::getTableName()
    );
};

$entries['domain.schedule.period.service.repository'] = function (Container $c) {
    return new Repository\Schedule\PeriodServiceRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersPeriodServiceTable::getTableName()
    );
};

$entries['domain.schedule.period.location.repository'] = function (Container $c) {
    return new Repository\Schedule\PeriodLocationRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersPeriodLocationTable::getTableName()
    );
};

$entries['domain.schedule.specialDay.repository'] = function (Container $c) {
    return new Repository\Schedule\SpecialDayRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersSpecialDayTable::getTableName()
    );
};

$entries['domain.schedule.specialDay.period.repository'] = function (Container $c) {
    return new Repository\Schedule\SpecialDayPeriodRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersSpecialDayPeriodTable::getTableName()
    );
};

$entries['domain.schedule.specialDay.period.service.repository'] = function (Container $c) {
    return new Repository\Schedule\SpecialDayPeriodServiceRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersSpecialDayPeriodServiceTable::getTableName()
    );
};

$entries['domain.schedule.specialDay.period.location.repository'] = function (Container $c) {
    return new Repository\Schedule\SpecialDayPeriodLocationRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersSpecialDayPeriodLocationTable::getTableName()
    );
};

$entries['domain.users.providers.repository'] = function (Container $c) {
    return new Repository\User\ProviderRepository(
        $c->getDatabaseConnection(),
        DB\User\UsersTable::getTableName(),
        DB\User\Provider\ProvidersWeekDayTable::getTableName(),
        DB\User\Provider\ProvidersPeriodTable::getTableName(),
        DB\User\Provider\ProvidersPeriodServiceTable::getTableName(),
        DB\User\Provider\ProvidersPeriodLocationTable::getTableName(),
        DB\User\Provider\ProvidersTimeOutTable::getTableName(),
        DB\User\Provider\ProvidersSpecialDayTable::getTableName(),
        DB\User\Provider\ProvidersSpecialDayPeriodTable::getTableName(),
        DB\User\Provider\ProvidersSpecialDayPeriodServiceTable::getTableName(),
        DB\User\Provider\ProvidersSpecialDayPeriodLocationTable::getTableName(),
        DB\User\Provider\ProvidersDayOffTable::getTableName(),
        DB\User\Provider\ProvidersServiceTable::getTableName(),
        DB\User\Provider\ProvidersLocationTable::getTableName(),
        DB\Bookable\ServicesTable::getTableName(),
        DB\Location\LocationsTable::getTableName(),
        DB\User\Provider\ProvidersViewsTable::getTableName(),
        DB\User\Provider\ProvidersGoogleCalendarTable::getTableName(),
        DB\User\Provider\ProvidersOutlookCalendarTable::getTableName()
    );
};

$entries['domain.users.customers.repository'] = function (Container $c) {
    return new Repository\User\CustomerRepository(
        $c->getDatabaseConnection(),
        DB\User\UsersTable::getTableName()
    );
};

$entries['domain.wpUsers.repository'] = function (Container $c) {
    return new Repository\User\WPUserRepository(
        $c->getDatabaseConnection(),
        DB\User\WPUsersTable::getTableName(),
        DB\User\WPUsersTable::getMetaTableName(),
        DB\User\WPUsersTable::getDatabasePrefix()
    );
};

$entries['domain.booking.appointment.repository'] = function (Container $c) {
    return new Repository\Booking\Appointment\AppointmentRepository(
        $c->getDatabaseConnection(),
        DB\Booking\AppointmentsTable::getTableName(),
        DB\Bookable\ServicesTable::getTableName(),
        DB\Booking\CustomerBookingsTable::getTableName(),
        DB\Booking\CustomerBookingsToExtrasTable::getTableName(),
        DB\Bookable\ExtrasTable::getTableName(),
        DB\User\UsersTable::getTableName(),
        DB\Payment\PaymentsTable::getTableName(),
        DB\Coupon\CouponsTable::getTableName(),
        DB\User\Provider\ProvidersLocationTable::getTableName(),
        DB\User\Provider\ProvidersServiceTable::getTableName(),
        DB\Bookable\PackagesCustomersTable::getTableName(),
        DB\Bookable\PackagesCustomersServicesTable::getTableName()
    );
};

$entries['domain.booking.customerBooking.repository'] = function (Container $c) {
    return new Repository\Booking\Appointment\CustomerBookingRepository(
        $c->getDatabaseConnection(),
        DB\Booking\CustomerBookingsTable::getTableName()
    );
};

$entries['domain.booking.customerBookingExtra.repository'] = function (Container $c) {
    return new Repository\Booking\Appointment\CustomerBookingExtraRepository(
        $c->getDatabaseConnection(),
        DB\Booking\CustomerBookingsToExtrasTable::getTableName()
    );
};

$entries['domain.booking.customerBookingEventPeriod.repository'] = function (Container $c) {
    return new Repository\Booking\Event\CustomerBookingEventPeriodRepository(
        $c->getDatabaseConnection(),
        DB\Booking\CustomerBookingsToEventsPeriodsTable::getTableName()
    );
};

$entries['domain.booking.customerBookingEventTicket.repository'] = function (Container $c) {
    return new Repository\Booking\Event\CustomerBookingEventTicketRepository(
        $c->getDatabaseConnection(),
        DB\Booking\CustomerBookingToEventsTicketsTable::getTableName()
    );
};

$entries['domain.galleries.repository'] = function (Container $c) {
    return new Repository\Gallery\GalleryRepository(
        $c->getDatabaseConnection(),
        DB\Gallery\GalleriesTable::getTableName()
    );
};

$entries['domain.google.calendar.repository'] = function (Container $c) {
    return new Repository\Google\GoogleCalendarRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersGoogleCalendarTable::getTableName()
    );
};

$entries['domain.outlook.calendar.repository'] = function (Container $c) {
    return new Repository\Outlook\OutlookCalendarRepository(
        $c->getDatabaseConnection(),
        DB\User\Provider\ProvidersOutlookCalendarTable::getTableName()
    );
};

$entries['domain.customField.repository'] = function (Container $c) {
    return new Repository\CustomField\CustomFieldRepository(
        $c->getDatabaseConnection(),
        DB\CustomField\CustomFieldsTable::getTableName(),
        DB\CustomField\CustomFieldsOptionsTable::getTableName(),
        DB\CustomField\CustomFieldsServicesTable::getTableName(),
        DB\Bookable\ServicesTable::getTableName(),
        DB\CustomField\CustomFieldsEventsTable::getTableName(),
        DB\Booking\EventsTable::getTableName()
    );
};

$entries['domain.customFieldOption.repository'] = function (Container $c) {
    return new Repository\CustomField\CustomFieldOptionRepository(
        $c->getDatabaseConnection(),
        DB\CustomField\CustomFieldsOptionsTable::getTableName()
    );
};

$entries['domain.customFieldService.repository'] = function (Container $c) {
    return new Repository\CustomField\CustomFieldServiceRepository(
        $c->getDatabaseConnection(),
        DB\CustomField\CustomFieldsServicesTable::getTableName()
    );
};

$entries['domain.customFieldEvent.repository'] = function (Container $c) {
    return new Repository\CustomField\CustomFieldEventRepository(
        $c->getDatabaseConnection(),
        DB\CustomField\CustomFieldsEventsTable::getTableName()
    );
};

$entries['domain.booking.event.repository'] = function (Container $c) {
    return new Repository\Booking\Event\EventRepository(
        $c->getDatabaseConnection(),
        DB\Booking\EventsTable::getTableName()
    );
};

$entries['domain.booking.event.period.repository'] = function (Container $c) {
    return new Repository\Booking\Event\EventPeriodsRepository(
        $c->getDatabaseConnection(),
        DB\Booking\EventsPeriodsTable::getTableName()
    );
};

$entries['domain.booking.event.tag.repository'] = function (Container $c) {
    return new Repository\Booking\Event\EventTagsRepository(
        $c->getDatabaseConnection(),
        DB\Booking\EventsTagsTable::getTableName()
    );
};

$entries['domain.booking.event.ticket.repository'] = function (Container $c) {
    return new Repository\Booking\Event\EventTicketRepository(
        $c->getDatabaseConnection(),
        DB\Booking\EventsTicketsTable::getTableName()
    );
};

$entries['domain.booking.event.provider.repository'] = function (Container $c) {
    return new Repository\Booking\Event\EventProvidersRepository(
        $c->getDatabaseConnection(),
        DB\Booking\EventsProvidersTable::getTableName()
    );
};

$entries['domain.cache.repository'] = function (Container $c) {
    return new Repository\Cache\CacheRepository(
        $c->getDatabaseConnection(),
        DB\Cache\CacheTable::getTableName()
    );
};
