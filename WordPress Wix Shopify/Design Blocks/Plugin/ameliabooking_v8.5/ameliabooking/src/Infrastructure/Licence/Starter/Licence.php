<?php

namespace AmeliaBooking\Infrastructure\Licence\Starter;

use AmeliaBooking\Application\Commands;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Routes;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use Slim\App;

/**
 * Class Licence
 *
 * @package AmeliaBooking\Infrastructure\Licence\Starter
 */
class Licence extends \AmeliaBooking\Infrastructure\Licence\Lite\Licence
{
    public static $premium = true;

    public static $licence = 'Starter';

    /**
     * @param Container $c
     */
    public static function getCommands($c)
    {
        return array_merge(
            parent::getCommands($c),
            [
                // Activation
                Commands\Activation\ActivatePluginCommand::class                   => new Commands\Activation\ActivatePluginCommandHandler($c),
                Commands\Activation\DeactivatePluginCommand::class                 => new Commands\Activation\DeactivatePluginCommandHandler($c),
                Commands\Activation\DeactivatePluginEnvatoCommand::class           => new Commands\Activation\DeactivatePluginEnvatoCommandHandler($c),
                Commands\Activation\ParseDomainCommand::class                      => new Commands\Activation\ParseDomainCommandHandler($c),
                // Bookable/Extra
                Commands\Bookable\Extra\AddExtraCommand::class                     => new Commands\Bookable\Extra\AddExtraCommandHandler($c),
                Commands\Bookable\Extra\DeleteExtraCommand::class                  => new Commands\Bookable\Extra\DeleteExtraCommandHandler($c),
                Commands\Bookable\Extra\GetExtraCommand::class                     => new Commands\Bookable\Extra\GetExtraCommandHandler($c),
                Commands\Bookable\Extra\GetExtrasCommand::class                    => new Commands\Bookable\Extra\GetExtrasCommandHandler($c),
                Commands\Bookable\Extra\UpdateExtraCommand::class                  => new Commands\Bookable\Extra\UpdateExtraCommandHandler($c),
                // Coupon
                Commands\Coupon\AddCouponCommand::class                            => new Commands\Coupon\AddCouponCommandHandler($c),
                Commands\Coupon\DeleteCouponCommand::class                         => new Commands\Coupon\DeleteCouponCommandHandler($c),
                Commands\Coupon\GetCouponCommand::class                            => new Commands\Coupon\GetCouponCommandHandler($c),
                Commands\Coupon\GetCouponsCommand::class                           => new Commands\Coupon\GetCouponsCommandHandler($c),
                Commands\Coupon\GetValidCouponCommand::class                       => new Commands\Coupon\GetValidCouponCommandHandler($c),
                Commands\Coupon\UpdateCouponCommand::class                         => new Commands\Coupon\UpdateCouponCommandHandler($c),
                Commands\Coupon\UpdateCouponStatusCommand::class                   => new Commands\Coupon\UpdateCouponStatusCommandHandler($c),
                // Report
                Commands\Report\GetAppointmentsCommand::class                      => new Commands\Report\GetAppointmentsCommandHandler($c),
                Commands\Report\GetCouponsCommand::class                           => new Commands\Report\GetCouponsCommandHandler($c),
                Commands\Report\GetCustomersCommand::class                         => new Commands\Report\GetCustomersCommandHandler($c),
                Commands\Import\ImportCustomersCommand::class                      => new Commands\Import\ImportCustomersCommandHandler($c),
                Commands\Report\GetPaymentsCommand::class                          => new Commands\Report\GetPaymentsCommandHandler($c),
                Commands\Report\GetEventAttendeesCommand::class                    => new Commands\Report\GetEventAttendeesCommandHandler($c),
                // Search
                Commands\Search\GetSearchCommand::class                            => new Commands\Search\GetSearchCommandHandler($c),
                // User/Customer
                Commands\User\Customer\ReauthorizeCommand::class                   => new Commands\User\Customer\ReauthorizeCommandHandler($c),
                Commands\User\Customer\UpdateCustomerStatusCommand::class          => new Commands\User\Customer\UpdateCustomerStatusCommandHandler($c),
                // User
                Commands\User\LoginCabinetCommand::class                           => new Commands\User\LoginCabinetCommandHandler($c),
                Commands\User\LogoutCabinetCommand::class                          => new Commands\User\LogoutCabinetCommandHandler($c),
                // User/Provider
                Commands\User\Provider\GetProviderCommand::class                   => new Commands\User\Provider\GetProviderCommandHandler($c),
                Commands\User\Provider\GetProvidersCommand::class                  => new Commands\User\Provider\GetProvidersCommandHandler($c),
                Commands\User\Provider\UpdateProviderStatusCommand::class          => new Commands\User\Provider\UpdateProviderStatusCommandHandler($c),
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

        Routes\Activation\Activation::routes($app);

        Routes\Coupon\Coupon::routes($app);

        Routes\Bookable\Extra::routes($app);

        Routes\Report\Report::routes($app);

        Routes\Search\Search::routes($app);

        Routes\Cabinet\Cabinet::routes($app);
    }

    /**
     * @return array
     */
    public static function getLiteMenuItem()
    {
        return [];
    }

    /**
     * @return array
     */
    public static function getEmployeesMenuItem()
    {
        return [
            'parentSlug' => 'amelia',
            'pageTitle'  => 'Employees',
            'menuTitle'  => BackendStrings::getCommonStrings()['employees'],
            'capability' => 'amelia_read_employees',
            'menuSlug'   => 'wpamelia-employees',
        ];
    }

    /**
     * @param Collection $providers
     *
     * @return Collection
     */
    public static function getEmployees($providers)
    {
        return $providers;
    }

    /**
     * @return string
     */
    public static function getPaddleUrl()
    {
        return 'https://cdn.paddle.com/paddle/paddle.js';
    }
}
