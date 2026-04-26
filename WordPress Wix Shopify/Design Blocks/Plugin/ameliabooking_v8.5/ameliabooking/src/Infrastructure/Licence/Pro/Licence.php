<?php

namespace AmeliaBooking\Infrastructure\Licence\Pro;

use AmeliaBooking\Application\Commands;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Routes;
use Slim\App;

/**
 * Class Licence
 *
 * @package AmeliaBooking\Infrastructure\Licence\Pro
 */
class Licence extends \AmeliaBooking\Infrastructure\Licence\Basic\Licence
{
    public static $licence = 'Pro';

    /**
     * @param Container $c
     */
    public static function getCommands($c)
    {
        return array_merge(
            parent::getCommands($c),
            [
                // Bookable/Package
                Commands\Bookable\Package\AddPackageCommand::class                 => new Commands\Bookable\Package\AddPackageCommandHandler($c),
                Commands\Bookable\Package\DeletePackageCommand::class              => new Commands\Bookable\Package\DeletePackageCommandHandler($c),
                Commands\Bookable\Package\GetPackagesCommand::class                => new Commands\Bookable\Package\GetPackagesCommandHandler($c),
                Commands\Bookable\Package\GetPackageDeleteEffectCommand::class     => new Commands\Bookable\Package\GetPackageDeleteEffectCommandHandler($c),
                Commands\Bookable\Package\UpdatePackageCommand::class              => new Commands\Bookable\Package\UpdatePackageCommandHandler($c),
                Commands\Bookable\Package\UpdatePackageStatusCommand::class        => new Commands\Bookable\Package\UpdatePackageStatusCommandHandler($c),
                Commands\Bookable\Package\DeletePackageCustomerCommand::class      => new Commands\Bookable\Package\DeletePackageCustomerCommandHandler($c),
                Commands\Bookable\Package\UpdatePackageCustomerCommand::class      => new Commands\Bookable\Package\UpdatePackageCustomerCommandHandler($c),
                Commands\Bookable\Package\AddPackageCustomerCommand::class         => new Commands\Bookable\Package\AddPackageCustomerCommandHandler($c),
                Commands\Bookable\Package\UpdatePackagesPositionsCommand::class    => new Commands\Bookable\Package\UpdatePackagesPositionsCommandHandler($c),
                // Bookable/Resource
                Commands\Bookable\Resource\AddResourceCommand::class               => new Commands\Bookable\Resource\AddResourceCommandHandler($c),
                Commands\Bookable\Resource\UpdateResourceCommand::class            => new Commands\Bookable\Resource\UpdateResourceCommandHandler($c),
                Commands\Bookable\Resource\UpdateResourceStatusCommand::class      => new Commands\Bookable\Resource\UpdateResourceStatusCommandHandler($c),
                Commands\Bookable\Resource\DeleteResourceCommand::class            => new Commands\Bookable\Resource\DeleteResourceCommandHandler($c),
                Commands\Bookable\Resource\GetResourcesCommand::class              => new Commands\Bookable\Resource\GetResourcesCommandHandler($c),
                // Notification
                Commands\Notification\SendTestWhatsAppCommand::class               => new Commands\Notification\SendTestWhatsAppCommandHandler($c),
                Commands\Notification\WhatsAppWebhookRegisterCommand::class        => new Commands\Notification\WhatsAppWebhookRegisterCommandHandler($c),
                Commands\Notification\WhatsAppWebhookCommand::class                => new Commands\Notification\WhatsAppWebhookCommandHandler($c),
                // Payment
                Commands\Payment\RefundPaymentCommand::class                       => new Commands\Payment\RefundPaymentCommandHandler($c),
                Commands\Payment\GetTransactionAmountCommand::class                => new Commands\Payment\GetTransactionAmountCommandHandler($c),
                // Stripe
                Commands\Stripe\GetStripeAccountCommand::class                     => new Commands\Stripe\GetStripeAccountCommandHandler($c),
                Commands\Stripe\GetStripeAccountsCommand::class                    => new Commands\Stripe\GetStripeAccountsCommandHandler($c),
                Commands\Stripe\GetStripeAccountDashboardUrlCommand::class         => new Commands\Stripe\GetStripeAccountDashboardUrlCommandHandler($c),
                Commands\Stripe\StripeOnboardRedirectCommand::class                => new Commands\Stripe\StripeOnboardRedirectCommandHandler($c),
                Commands\Stripe\StripeAccountDisconnectCommand::class              => new Commands\Stripe\StripeAccountDisconnectCommandHandler($c),
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

        Routes\Bookable\Resource::routes($app);

        Routes\Bookable\Package::routes($app);

        Routes\Payment\Refund::routes($app);

        Routes\Stripe\Stripe::routes($app);

        Routes\Notification\WhatsApp::routes($app);
    }
}
