<?php

namespace AmeliaBooking\Infrastructure\Licence\Basic;

use AmeliaBooking\Domain\Services as DomainServices;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Services as InfrastructureServices;

/**
 * Class InfrastructureService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Basic
 */
class InfrastructureService extends \AmeliaBooking\Infrastructure\Licence\Starter\InfrastructureService
{
    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Google\AbstractGoogleCalendarService
     */
    public static function getCalendarGoogleService($c)
    {
        return new InfrastructureServices\Google\GoogleCalendarService($c);
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Outlook\AbstractOutlookCalendarService
     */
    public static function getCalendarOutlookService($c)
    {
        return new InfrastructureServices\Outlook\OutlookCalendarService($c);
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Apple\AbstractAppleCalendarService
     */
    public static function getAppleCalendarService($c)
    {
        return new InfrastructureServices\Apple\AppleCalendarService($c);
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Mailchimp\AbstractMailchimpService
     */
    public static function getMailchimpService($c)
    {
        return new InfrastructureServices\Mailchimp\MailchimpService($c);
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Zoom\AbstractZoomService
     */
    public static function getZoomService($c)
    {
        return new InfrastructureServices\Zoom\ZoomService(
            $c->get('domain.settings.service')
        );
    }

    /**
     * @param Container $c
     *
     * @return DomainServices\Payment\PaymentServiceInterface
     */
    public static function getPayPalService($c)
    {
        return new InfrastructureServices\Payment\PayPalService(
            $c->get('domain.settings.service'),
            new InfrastructureServices\Payment\CurrencyService(
                $c->get('domain.settings.service')
            )
        );
    }

    /**
     * @param Container $c
     *
     * @return DomainServices\Payment\PaymentServiceInterface
     */
    public static function getStripeService($c)
    {
        return new InfrastructureServices\Payment\StripeService(
            $c->get('domain.settings.service'),
            new InfrastructureServices\Payment\CurrencyService(
                $c->get('domain.settings.service')
            )
        );
    }

    /**
     * @param Container $c
     *
     * @return DomainServices\Payment\PaymentServiceInterface
     */
    public static function getMollieService($c)
    {
        return new InfrastructureServices\Payment\MollieService(
            $c->get('domain.settings.service'),
            new InfrastructureServices\Payment\CurrencyService(
                $c->get('domain.settings.service')
            )
        );
    }

    /**
     * @param Container $c
     *
     * @return DomainServices\Payment\PaymentServiceInterface
     */
    public static function getRazorpayService($c)
    {
        return new InfrastructureServices\Payment\RazorpayService(
            $c->get('domain.settings.service'),
            new InfrastructureServices\Payment\CurrencyService(
                $c->get('domain.settings.service')
            )
        );
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Authentication\AbstractSocialAuthenticationService
     */
    public static function getSocialAuthenticationService($c)
    {
        return new InfrastructureServices\Authentication\SocialAuthenticationService($c);
    }
}
