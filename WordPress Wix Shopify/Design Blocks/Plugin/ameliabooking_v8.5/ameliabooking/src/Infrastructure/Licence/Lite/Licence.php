<?php

namespace AmeliaBooking\Infrastructure\Licence\Lite;

use AmeliaBooking\Application\Commands;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Routes;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use Slim\App;

/**
 * Class Licence
 *
 * @package AmeliaBooking\Infrastructure\Licence\Lite
 */
class Licence
{
    public static $premium = false;

    public static $licence = 'Lite';

    /**
     * @param Container $c
     */
    public static function getCommands($c)
    {
        return [
            // Test
            Commands\Test\TestCommand::class                                   => new Commands\Test\TestCommandHandler($c),
            // Stash
            Commands\Stash\UpdateStashCommand::class                           => new Commands\Stash\UpdateStashCommandHandler($c),
            // Bookable/Category
            Commands\Bookable\Category\AddCategoryCommand::class               => new Commands\Bookable\Category\AddCategoryCommandHandler($c),
            Commands\Bookable\Category\DeleteCategoryCommand::class            => new Commands\Bookable\Category\DeleteCategoryCommandHandler($c),
            Commands\Bookable\Category\GetCategoriesCommand::class             => new Commands\Bookable\Category\GetCategoriesCommandHandler($c),
            Commands\Bookable\Category\GetCategoryCommand::class               => new Commands\Bookable\Category\GetCategoryCommandHandler($c),
            Commands\Bookable\Category\UpdateCategoriesPositionsCommand::class => new Commands\Bookable\Category\UpdateCategoriesPositionsCommandHandler($c),
            Commands\Bookable\Category\UpdateCategoryCommand::class            => new Commands\Bookable\Category\UpdateCategoryCommandHandler($c),
            // Bookable/Service
            Commands\Bookable\Service\AddServiceCommand::class                 => new Commands\Bookable\Service\AddServiceCommandHandler($c),
            Commands\Bookable\Service\DeleteServiceCommand::class              => new Commands\Bookable\Service\DeleteServiceCommandHandler($c),
            Commands\Bookable\Service\GetServiceCommand::class                 => new Commands\Bookable\Service\GetServiceCommandHandler($c),
            Commands\Bookable\Service\GetServiceDeleteEffectCommand::class     => new Commands\Bookable\Service\GetServiceDeleteEffectCommandHandler($c),
            Commands\Bookable\Service\GetServicesCommand::class                => new Commands\Bookable\Service\GetServicesCommandHandler($c),
            Commands\Bookable\Service\UpdateServiceCommand::class              => new Commands\Bookable\Service\UpdateServiceCommandHandler($c),
            Commands\Bookable\Service\UpdateServiceStatusCommand::class        => new Commands\Bookable\Service\UpdateServiceStatusCommandHandler($c),
            Commands\Bookable\Service\UpdateServicesPositionsCommand::class    => new Commands\Bookable\Service\UpdateServicesPositionsCommandHandler($c),
            // Booking/Event
            Commands\Booking\Event\AddEventCommand::class                      => new Commands\Booking\Event\AddEventCommandHandler($c),
            Commands\Booking\Event\GetEventCommand::class                      => new Commands\Booking\Event\GetEventCommandHandler($c),
            Commands\Booking\Event\GetEventsCommand::class                     => new Commands\Booking\Event\GetEventsCommandHandler($c),
            Commands\Booking\Event\GetEventBookingsCommand::class              => new Commands\Booking\Event\GetEventBookingsCommandHandler($c),
            Commands\Booking\Event\UpdateEventCommand::class                   => new Commands\Booking\Event\UpdateEventCommandHandler($c),
            Commands\Booking\Event\UpdateEventStatusCommand::class             => new Commands\Booking\Event\UpdateEventStatusCommandHandler($c),
            Commands\Booking\Event\DeleteEventBookingCommand::class            => new Commands\Booking\Event\DeleteEventBookingCommandHandler($c),
            Commands\Booking\Event\UpdateEventBookingCommand::class            => new Commands\Booking\Event\UpdateEventBookingCommandHandler($c),
            Commands\Booking\Event\DeleteEventCommand::class                   => new Commands\Booking\Event\DeleteEventCommandHandler($c),
            Commands\Booking\Event\GetEventDeleteEffectCommand::class          => new Commands\Booking\Event\GetEventDeleteEffectCommandHandler($c),
            Commands\Booking\Event\GetCalendarEventsCommand::class             => new Commands\Booking\Event\GetCalendarEventsCommandHandler($c),
            // Booking/Appointment
            Commands\Booking\Appointment\AddAppointmentCommand::class          => new Commands\Booking\Appointment\AddAppointmentCommandHandler($c),
            Commands\Booking\Appointment\AddBookingCommand::class              => new Commands\Booking\Appointment\AddBookingCommandHandler($c),
            Commands\Booking\Appointment\DeleteBookingCommand::class           => new Commands\Booking\Appointment\DeleteBookingCommandHandler($c),
            Commands\Booking\Appointment\UpdateBookingStatusCommand::class     => new Commands\Booking\Appointment\UpdateBookingStatusCommandHandler($c),
            Commands\Booking\Appointment\CancelBookingCommand::class           => new Commands\Booking\Appointment\CancelBookingCommandHandler($c),
            Commands\Booking\Appointment\CancelBookingRemotelyCommand::class   => new Commands\Booking\Appointment\CancelBookingRemotelyCommandHandler($c),
            Commands\Booking\Appointment\RejectBookingRemotelyCommand::class   => new Commands\Booking\Appointment\RejectBookingRemotelyCommandHandler($c),
            Commands\Booking\Appointment\ApproveBookingRemotelyCommand::class  => new Commands\Booking\Appointment\ApproveBookingRemotelyCommandHandler($c),
            Commands\Booking\Appointment\DeleteAppointmentCommand::class       => new Commands\Booking\Appointment\DeleteAppointmentCommandHandler($c),
            Commands\Booking\Appointment\GetAppointmentCommand::class          => new Commands\Booking\Appointment\GetAppointmentCommandHandler($c),
            Commands\Booking\Appointment\GetAppointmentsCommand::class         => new Commands\Booking\Appointment\GetAppointmentsCommandHandler($c),
            Commands\Booking\Appointment\GetPackageAppointmentsCommand::class  => new Commands\Booking\Appointment\GetPackageAppointmentsCommandHandler($c),
            Commands\Booking\Appointment\GetIcsCommand::class                  => new Commands\Booking\Appointment\GetIcsCommandHandler($c),
            Commands\Booking\Appointment\GetTimeSlotsCommand::class            => new Commands\Booking\Appointment\GetTimeSlotsCommandHandler($c),
            Commands\Booking\Appointment\UpdateAppointmentCommand::class       => new Commands\Booking\Appointment\UpdateAppointmentCommandHandler($c),
            Commands\Booking\Appointment\UpdateAppointmentStatusCommand::class => new Commands\Booking\Appointment\UpdateAppointmentStatusCommandHandler($c),
            Commands\Booking\Appointment\UpdateAppointmentTimeCommand::class   => new Commands\Booking\Appointment\UpdateAppointmentTimeCommandHandler($c),
            Commands\Booking\Appointment\ReassignBookingCommand::class         => new Commands\Booking\Appointment\ReassignBookingCommandHandler($c),
            Commands\Booking\Appointment\SuccessfulBookingCommand::class       => new Commands\Booking\Appointment\SuccessfulBookingCommandHandler($c),
            // Entities
            Commands\Entities\GetEntitiesCommand::class                        => new Commands\Entities\GetEntitiesCommandHandler($c),
            // Notification
            Commands\Notification\GetNotificationsCommand::class               => new Commands\Notification\GetNotificationsCommandHandler($c),
            Commands\Notification\SendUndeliveredNotificationsCommand::class   => new Commands\Notification\SendUndeliveredNotificationsCommandHandler($c),
            Commands\Notification\SendTestEmailCommand::class                  => new Commands\Notification\SendTestEmailCommandHandler($c),
            Commands\Notification\UpdateNotificationCommand::class             => new Commands\Notification\UpdateNotificationCommandHandler($c),
            Commands\Notification\UpdateNotificationStatusCommand::class       => new Commands\Notification\UpdateNotificationStatusCommandHandler($c),
            Commands\Notification\SendAmeliaSmsApiRequestCommand::class        => new Commands\Notification\SendAmeliaSmsApiRequestCommandHandler($c),
            Commands\Notification\UpdateSMSNotificationHistoryCommand::class   => new Commands\Notification\UpdateSMSNotificationHistoryCommandHandler($c),
            Commands\Notification\GetSMSNotificationsHistoryCommand::class     => new Commands\Notification\GetSMSNotificationsHistoryCommandHandler($c),
            // Payment
            Commands\Payment\AddPaymentCommand::class                          => new Commands\Payment\AddPaymentCommandHandler($c),
            Commands\Payment\DeletePaymentCommand::class                       => new Commands\Payment\DeletePaymentCommandHandler($c),
            Commands\Payment\GetPaymentCommand::class                          => new Commands\Payment\GetPaymentCommandHandler($c),
            Commands\Payment\GetPaymentsCommand::class                         => new Commands\Payment\GetPaymentsCommandHandler($c),
            Commands\Payment\UpdatePaymentCommand::class                       => new Commands\Payment\UpdatePaymentCommandHandler($c),
            Commands\Payment\CalculatePaymentAmountCommand::class              => new Commands\Payment\CalculatePaymentAmountCommandHandler($c),
            Commands\Square\SquarePaymentCommand::class                        => new Commands\Square\SquarePaymentCommandHandler($c),
            Commands\Square\SquarePaymentNotifyCommand::class                  => new Commands\Square\SquarePaymentNotifyCommandHandler($c),
            //Square
            Commands\Square\DisconnectFromSquareAccountCommand::class          => new Commands\Square\DisconnectFromSquareAccountCommandHandler($c),
            Commands\Square\FetchAccessTokenSquareCommand::class               => new Commands\Square\FetchAccessTokenSquareCommandHandler($c),
            Commands\Square\GetSquareAuthURLCommand::class                     => new Commands\Square\GetSquareAuthURLCommandHandler($c),
            Commands\Square\SquareRefundWebhookCommand::class                  => new Commands\Square\SquareRefundWebhookCommandHandler($c),
            // Settings
            Commands\Settings\GetSettingsCommand::class                        => new Commands\Settings\GetSettingsCommandHandler($c),
            Commands\Settings\UpdateSettingsCommand::class                     => new Commands\Settings\UpdateSettingsCommandHandler($c),
            // Status
            Commands\Stats\AddStatsCommand::class                              => new Commands\Stats\AddStatsCommandHandler($c),
            Commands\Stats\GetStatsCommand::class                              => new Commands\Stats\GetStatsCommandHandler($c),
            // User/Customer
            Commands\User\Customer\AddCustomerCommand::class                   => new Commands\User\Customer\AddCustomerCommandHandler($c),
            Commands\User\Customer\GetCustomerCommand::class                   => new Commands\User\Customer\GetCustomerCommandHandler($c),
            Commands\User\Customer\GetCustomersCommand::class                  => new Commands\User\Customer\GetCustomersCommandHandler($c),
            Commands\User\Customer\UpdateCustomerCommand::class                => new Commands\User\Customer\UpdateCustomerCommandHandler($c),
            // User
            Commands\User\DeleteUserCommand::class                             => new Commands\User\DeleteUserCommandHandler($c),
            Commands\User\GetCurrentUserCommand::class                         => new Commands\User\GetCurrentUserCommandHandler($c),
            Commands\User\GetUserDeleteEffectCommand::class                    => new Commands\User\GetUserDeleteEffectCommandHandler($c),
            Commands\User\GetWPUsersCommand::class                             => new Commands\User\GetWPUsersCommandHandler($c),
            // User/Provider
            Commands\User\Provider\AddProviderCommand::class                   => new Commands\User\Provider\AddProviderCommandHandler($c),
            Commands\User\Provider\UpdateProviderCommand::class                => new Commands\User\Provider\UpdateProviderCommandHandler($c),
            // What's new
            Commands\WhatsNew\GetWhatsNewCommand::class                        => new Commands\WhatsNew\GetWhatsNewCommandHandler($c),
        ];
    }

    /**
     * @param App       $app
     * @param Container $container
     */
    public static function setRoutes(App $app, Container $container)
    {
        Routes\Booking\Booking::routes($app);

        Routes\Booking\Appointment\Appointment::routes($app);

        Routes\Booking\Event\Event::routes($app);

        Routes\Bookable\Category::routes($app);

        Routes\Entities\Entities::routes($app);

        Routes\Stash\Stash::routes($app);

        Routes\Notification\Notification::routes($app);

        Routes\Payment\Payment::routes($app);

        Routes\Square\Square::routes($app);

        Routes\Import\Import::routes($app);

        Routes\Bookable\Service::routes($app);

        Routes\Settings\Settings::routes($app);

        Routes\Stats\Stats::routes($app);

        Routes\TimeSlots\TimeSlots::routes($app);

        Routes\User\User::routes($app);

        Routes\WhatsNew\WhatsNew::routes($app);

        Routes\Test\Test::routes($app);
    }

    /**
     * @return array
     */
    public static function getLiteMenuItem()
    {
        return [
            'parentSlug' => 'amelia',
            'pageTitle'  => 'Lite vs Premium',
            'menuTitle'  => BackendStrings::getCommonStrings()['lite_vs_premium'],
            'capability' => 'amelia_read_lite_vs_premium',
            'menuSlug'   => 'wpamelia-lite-vs-premium',
        ];
    }

    /**
     * @return array
     */
    public static function getEmployeesMenuItem()
    {
        return [];
    }

    /**
     * @param Collection $providers
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function getEmployees($providers)
    {
        /** @var Collection $availableProviders */
        $availableProviders = new Collection();

        if ($providers->length()) {
            $availableProviders->addItem($providers->getItem($providers->keys()[0]), $providers->keys()[0]);
        }

        return $availableProviders;
    }

    /**
     * @return string
     */
    public static function getPaddleUrl()
    {
        return AMELIA_URL . 'public/js/paddle/paddle.js';
    }
}
