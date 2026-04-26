<?php

/**
 * Assembling application services:
 * Instantiating application services and injecting the Infrastructure layer implementations
 */

use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Cache\CacheApplicationService;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\Gallery\GalleryApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\Reservation\ReservationService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Infrastructure\Common\Container;

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Entities service
 *
 * @param Container $c
 *
 * @return EntityApplicationService
 */
$entries['application.entity.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Entity\EntityApplicationService($c);
};

/**
 * Customer service
 *
 * @param Container $c
 *
 * @return UserApplicationService
 */
$entries['application.user.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\User\UserApplicationService($c);
};

/**
 * API User Application service
 *
 * @param $c
 *
 * @return AmeliaBooking\Application\Services\User\UserApplicationService
 */
$entries['application.api.user.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getApiService(
        $c
    );
};

/**
 * Provider service
 *
 * @param Container $c
 *
 * @return ProviderApplicationService
 */
$entries['application.user.provider.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\User\ProviderApplicationService($c);
};

/**
 * Customer service
 *
 * @param Container $c
 *
 * @return CustomerApplicationService
 */
$entries['application.user.customer.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\User\CustomerApplicationService($c);
};

/**
 * Current Location Service
 *
 * @return AmeliaBooking\Application\Services\Location\AbstractCurrentLocation
 */
$entries['application.currentLocation.service'] = function () {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getCurrentLocationService();
};

/**
 * Deposit Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Deposit\AbstractDepositApplicationService
 */
$entries['application.deposit.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getDepositService($c);
};

/**
 * Extra Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Extra\AbstractExtraApplicationService
 */
$entries['application.extra.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getExtraService($c);
};

/**
 * Appointment service
 *
 * @param Container $c
 *
 * @return AppointmentApplicationService
 */
$entries['application.booking.appointment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Booking\AppointmentApplicationService($c);
};

/**
 * Event service
 *
 * @param Container $c
 *
 * @return EventApplicationService
 */
$entries['application.booking.event.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Booking\EventApplicationService($c);
};

/**
 * Reservation service
 *
 * @param Container $c
 *
 * @return ReservationService
 */
$entries['application.reservation.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Reservation\ReservationService($c);
};

/**
 * Reservation
 *
 * @param bool $validate
 *
 * @return Reservation
 */
$entries['application.reservation'] = function () {
    return new AmeliaBooking\Domain\Entity\Booking\Reservation();
};

/**
 * Appointment Reservation service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Reservation\AppointmentReservationService
 */
$entries['application.reservation.appointment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Reservation\AppointmentReservationService($c);
};

/**
 * Package Reservation service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Reservation\PackageReservationService
 */
$entries['application.reservation.package.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Reservation\PackageReservationService($c);
};

/**
 * Event Reservation service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Reservation\EventReservationService
 */
$entries['application.reservation.event.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Reservation\EventReservationService($c);
};

/**
 * Booking service
 *
 * @param Container $c
 *
 * @return BookingApplicationService
 */
$entries['application.booking.booking.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Booking\BookingApplicationService($c);
};

/**
 * Bookable service
 *
 * @param Container $c
 *
 * @return BookableApplicationService
 */
$entries['application.bookable.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Bookable\BookableApplicationService($c);
};

/**
 * Bookable package
 *
 * @param Container $c
 *
 * @return AbstractPackageApplicationService
 */
$entries['application.bookable.package'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getPackageService($c);
};

/**
 * Resource service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Resource\AbstractResourceApplicationService
 */
$entries['application.resource.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getResourceService($c);
};

/**
 * Gallery service
 *
 * @param Container $c
 *
 * @return GalleryApplicationService
 */
$entries['application.gallery.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Gallery\GalleryApplicationService($c);
};

/**
 * Calendar service
 *
 * @param Container $c
 *
 * @return TimeSlotService
 */
$entries['application.timeSlot.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\TimeSlot\TimeSlotService($c);
};

/**
 * Cache service
 *
 * @param Container $c
 *
 * @return CacheApplicationService
 */
$entries['application.cache.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Cache\CacheApplicationService($c);
};

/**
 * Tax service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Tax\AbstractTaxApplicationService
 */
$entries['application.tax.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getTaxService($c);
};

/**
 * Coupon service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Coupon\AbstractCouponApplicationService
 */
$entries['application.coupon.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getCouponService($c);
};

/**
 * Location Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Location\AbstractLocationApplicationService
 */
$entries['application.location.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getLocationService($c);
};

/**
 * Notification Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Notification\ApplicationNotificationService
 */
$entries['application.notification.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\ApplicationNotificationService($c);
};

/**
 * Appointment Notification Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Notification\AppointmentNotificationService
 */
$entries['application.notification.appointment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\AppointmentNotificationService($c);
};

/**
 * Email Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\EmailNotificationService
 */
$entries['application.emailNotification.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\EmailNotificationService($c, 'email');
};

/**
 * Notification Helper Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\NotificationHelperService
 */
$entries['application.notificationHelper.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\NotificationHelperService($c);
};

/**
 * SMS Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\SMSNotificationService
 */
$entries['application.smsNotification.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\SMSNotificationService($c, 'sms');
};


/**
 * WhatsApp Notification Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService
 */
$entries['application.whatsAppNotification.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getWhatsAppNotificationService($c);
};

/**
 * Appointment Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Placeholder\AppointmentPlaceholderService
 */
$entries['application.placeholder.appointment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Placeholder\AppointmentPlaceholderService($c);
};

/**
 * Appointments Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Placeholder\AppointmentsPlaceholderService
 */
$entries['application.placeholder.appointments.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Placeholder\AppointmentsPlaceholderService($c);
};

/**
 * Package Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Placeholder\PackagePlaceholderService
 */
$entries['application.placeholder.package.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Placeholder\PackagePlaceholderService($c);
};

/**
 * Event Notification Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Placeholder\EventPlaceholderService
 */
$entries['application.placeholder.event.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Placeholder\EventPlaceholderService($c);
};

/**
 * Stats Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Stats\StatsService
 */
$entries['application.stats.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Stats\StatsService($c);
};

/**
 * Helper Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Helper\HelperService
 */
$entries['application.helper.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Helper\HelperService($c);
};

/**
 * Settings Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Settings\SettingsService
 */
$entries['application.settings.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Settings\SettingsService($c);
};

/**
 * SMS API Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\SMSAPIService
 */
$entries['application.smsApi.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\SMSAPIService($c);
};

/**
 * WhatsApp Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Notification\WhatsAppService
 */
$entries['application.whatsApp.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Notification\WhatsAppService($c);
};

/**
 * Payment service
 *
 * @param Container $c
 *
 * @return PaymentApplicationService
 */
$entries['application.payment.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Payment\PaymentApplicationService($c);
};

/**
 * Invoice service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Invoice\AbstractInvoiceApplicationService
 */
$entries['application.invoice.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getInvoiceService($c);
};

/**
 * Custom Field Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService
 */
$entries['application.customField.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getCustomFieldService($c);
};

/**
 * Web Hook Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService
 */
$entries['application.webHook.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getWebHookService($c);
};

/**
 * Integration Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService
 */
$entries['application.integration.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService($c);
};

/**
 * Zoom Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Zoom\AbstractZoomApplicationService
 */
$entries['application.zoom.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\ApplicationService::getZoomService($c);
};

/**
 * ICS File Service
 *
 * @param Container $c
 *
 * @return \AmeliaBooking\Application\Services\Booking\IcsApplicationService
 */
$entries['application.ics.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Booking\IcsApplicationService($c);
};

/**
 * Stash Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Application\Services\Stash\StashApplicationService
 */
$entries['application.stash.service'] = function ($c) {
    return new AmeliaBooking\Application\Services\Stash\StashApplicationService($c);
};
