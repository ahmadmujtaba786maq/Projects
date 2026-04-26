<?php

namespace AmeliaBooking\Infrastructure\Licence\Basic;

use AmeliaBooking\Application\Commands;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Routes;
use Slim\App;

/**
 * Class Licence
 *
 * @package AmeliaBooking\Infrastructure\Licence\Basic
 */
class Licence extends \AmeliaBooking\Infrastructure\Licence\Starter\Licence
{
    public static $licence = 'Basic';

    /**
     * @param Container $c
     */
    public static function getCommands($c)
    {
        return array_merge(
            parent::getCommands($c),
            [
                // Booking
                Commands\Booking\Appointment\DeleteBookingRemotelyCommand::class   => new Commands\Booking\Appointment\DeleteBookingRemotelyCommandHandler($c),
                // Location
                Commands\Location\AddLocationCommand::class                        => new Commands\Location\AddLocationCommandHandler($c),
                Commands\Location\DeleteLocationCommand::class                     => new Commands\Location\DeleteLocationCommandHandler($c),
                Commands\Location\GetLocationCommand::class                        => new Commands\Location\GetLocationCommandHandler($c),
                Commands\Location\GetLocationDeleteEffectCommand::class            => new Commands\Location\GetLocationDeleteEffectCommandHandler($c),
                Commands\Location\GetLocationsCommand::class                       => new Commands\Location\GetLocationsCommandHandler($c),
                Commands\Location\UpdateLocationCommand::class                     => new Commands\Location\UpdateLocationCommandHandler($c),
                Commands\Location\UpdateLocationStatusCommand::class               => new Commands\Location\UpdateLocationStatusCommandHandler($c),
                // CustomField
                Commands\CustomField\GetCustomFieldsCommand::class                 => new Commands\CustomField\GetCustomFieldsCommandHandler($c),
                Commands\CustomField\GetCustomFieldFileCommand::class              => new Commands\CustomField\GetCustomFieldFileCommandHandler($c),
                Commands\CustomField\AddCustomFieldCommand::class                  => new Commands\CustomField\AddCustomFieldCommandHandler($c),
                Commands\CustomField\DeleteCustomFieldCommand::class               => new Commands\CustomField\DeleteCustomFieldCommandHandler($c),
                Commands\CustomField\UpdateCustomFieldCommand::class               => new Commands\CustomField\UpdateCustomFieldCommandHandler($c),
                Commands\CustomField\UpdateCustomFieldsPositionsCommand::class     => new Commands\CustomField\UpdateCustomFieldsPositionsCommandHandler($c),
                // Google
                Commands\Google\DisconnectFromGoogleAccountCommand::class          => new Commands\Google\DisconnectFromGoogleAccountCommandHandler($c),
                Commands\Google\FetchAccessTokenWithAuthCodeCommand::class         => new Commands\Google\FetchAccessTokenWithAuthCodeCommandHandler($c),
                Commands\Google\GetGoogleAuthURLCommand::class                     => new Commands\Google\GetGoogleAuthURLCommandHandler($c),
                // Outlook
                Commands\Outlook\DisconnectFromOutlookAccountCommand::class        => new Commands\Outlook\DisconnectFromOutlookAccountCommandHandler($c),
                Commands\Outlook\GetOutlookAuthURLCommand::class                   => new Commands\Outlook\GetOutlookAuthURLCommandHandler($c),
                Commands\Outlook\FetchAccessTokenWithAuthCodeOutlookCommand::class =>
                    new Commands\Outlook\FetchAccessTokenWithAuthCodeOutlookCommandHandler($c),
                // Mailchimp
                Commands\Mailchimp\GetMailchimpAuthURLCommand::class               => new Commands\Mailchimp\GetMailchimpAuthURLCommandHandler($c),
                Commands\Mailchimp\FetchAccessTokenMailchimpCommand::class         => new Commands\Mailchimp\FetchAccessTokenMailchimpCommandHandler($c),
                Commands\Mailchimp\DisconnectFromMailchimpCommand::class           => new Commands\Mailchimp\DisconnectFromMailchimpCommandHandler($c),
                // Notification
                Commands\Notification\AddNotificationCommand::class                => new Commands\Notification\AddNotificationCommandHandler($c),
                Commands\Notification\DeleteNotificationCommand::class             => new Commands\Notification\DeleteNotificationCommandHandler($c),
                Commands\Notification\SendScheduledNotificationsCommand::class     => new Commands\Notification\SendScheduledNotificationsCommandHandler($c),
                // Payment
                Commands\PaymentGateway\PayPalPaymentCallbackCommand::class        => new Commands\PaymentGateway\PayPalPaymentCallbackCommandHandler($c),
                Commands\PaymentGateway\PayPalPaymentCommand::class                => new Commands\PaymentGateway\PayPalPaymentCommandHandler($c),
                Commands\PaymentGateway\WooCommercePaymentCommand::class           => new Commands\PaymentGateway\WooCommercePaymentCommandHandler($c),
                Commands\PaymentGateway\WooCommerceProductsCommand::class          => new Commands\PaymentGateway\WooCommerceProductsCommandHandler($c),
                Commands\PaymentGateway\MolliePaymentNotifyCommand::class          => new Commands\PaymentGateway\MolliePaymentNotifyCommandHandler($c),
                Commands\PaymentGateway\MolliePaymentCommand::class                => new Commands\PaymentGateway\MolliePaymentCommandHandler($c),
                Commands\PaymentGateway\RazorpayPaymentCommand::class              => new Commands\PaymentGateway\RazorpayPaymentCommandHandler($c),
                Commands\Payment\PaymentCallbackCommand::class                     => new Commands\Payment\PaymentCallbackCommandHandler($c),
                Commands\Payment\PaymentLinkCommand::class                         => new Commands\Payment\PaymentLinkCommandHandler($c),
                // Tax
                Commands\Tax\AddTaxCommand::class                                  => new Commands\Tax\AddTaxCommandHandler($c),
                Commands\Tax\DeleteTaxCommand::class                               => new Commands\Tax\DeleteTaxCommandHandler($c),
                Commands\Tax\GetTaxCommand::class                                  => new Commands\Tax\GetTaxCommandHandler($c),
                Commands\Tax\GetTaxesCommand::class                                => new Commands\Tax\GetTaxesCommandHandler($c),
                Commands\Tax\UpdateTaxCommand::class                               => new Commands\Tax\UpdateTaxCommandHandler($c),
                Commands\Tax\UpdateTaxStatusCommand::class                         => new Commands\Tax\UpdateTaxStatusCommandHandler($c),
                // Zoom
                Commands\Zoom\GetUsersCommand::class                               => new Commands\Zoom\GetUsersCommandHandler($c),
                // Invoices
                Commands\Invoice\GenerateInvoiceCommand::class                     => new Commands\Invoice\GenerateInvoiceCommandHandler($c),
                // Apple
                Commands\Apple\GetAppleCalendarListCommand::class                  => new Commands\Apple\GetAppleCalendarListCommandHandler($c),
                Commands\Apple\DisconnectFromAppleCalendarCommand::class           => new Commands\Apple\DisconnectFromAppleCalendarCommandHandler($c),
                Commands\Apple\ConnectEmployeeToPersonalAppleCalendarCommand::class =>
                    new Commands\Apple\ConnectEmployeeToPersonalAppleCalendarCommandHandler($c),
                Commands\Apple\DisconnectEmployeeFromAppleCalendarCommand::class    => new Commands\Apple\DisconnectEmployeeFromAppleCalendarCommandHandler($c),
                // User
                Commands\User\SocialLoginCommand::class                            => new Commands\User\SocialLoginCommandHandler($c),
            ]
        );
    }

    /**
     * @param App       $app
     * @param Container $container
     */
    public static function setRoutes(App $app, Container $container)
    {
        parent::setRoutes($app, $container);

        Routes\Location\Location::routes($app);

        Routes\Google\Google::routes($app);

        Routes\Outlook\Outlook::routes($app);

        Routes\Mailchimp\Mailchimp::routes($app);

        Routes\PaymentGateway\PaymentGateway::routes($app);

        Routes\Payment\PaymentLink::routes($app);

        Routes\CustomField\CustomField::routes($app);

        Routes\Tax\Tax::routes($app);

        Routes\Invoice\Invoice::routes($app);

        Routes\Zoom\Zoom::routes($app);

        Routes\Apple\Apple::routes($app);

        Routes\SocialLogin\SocialLogin::routes($app);
    }
}
