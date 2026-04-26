<?php

namespace AmeliaBooking\Infrastructure\Licence\Lite;

use AmeliaBooking\Domain\Services as DomainServices;
use AmeliaBooking\Infrastructure\Services as InfrastructureServices;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class InfrastructureService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Lite
 */
class InfrastructureService
{
    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Google\AbstractGoogleCalendarService
     */
    public static function getCalendarGoogleService($c)
    {
        return new InfrastructureServices\Google\StarterGoogleCalendarService($c);
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Outlook\AbstractOutlookCalendarService
     */
    public static function getCalendarOutlookService($c)
    {
        return new InfrastructureServices\Outlook\StarterOutlookCalendarService($c);
    }

    /**
     * @param $c
     * @return InfrastructureServices\Apple\AbstractAppleCalendarService
     */
    public static function getAppleCalendarService($c)
    {
        return new InfrastructureServices\Apple\StarterAppleCalendarCalendarService($c);
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Mailchimp\AbstractMailchimpService
     */
    public static function getMailchimpService($c)
    {
        return new InfrastructureServices\Mailchimp\StarterMailchimpService($c);
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Recaptcha\AbstractRecaptchaService
     */
    public static function getRecaptchaService($c)
    {
        return new InfrastructureServices\Recaptcha\LiteRecaptchaService(
            $c->get('domain.settings.service')
        );
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\LessonSpace\AbstractLessonSpaceService
     */
    public static function getLessonSpaceService($c)
    {
        return new InfrastructureServices\LessonSpace\LiteLessonSpaceService(
            $c,
            $c->get('domain.settings.service')
        );
    }

    /**
     * @param Container $c
     *
     * @return InfrastructureServices\Zoom\AbstractZoomService
     */
    public static function getZoomService($c)
    {
        return new InfrastructureServices\Zoom\StarterZoomService(
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
        return new InfrastructureServices\Payment\StarterPaymentService(
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
        return new InfrastructureServices\Payment\StarterPaymentService(
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
        return new InfrastructureServices\Payment\StarterPaymentService(
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
        return new InfrastructureServices\Payment\StarterPaymentService(
            $c->get('domain.settings.service'),
            new InfrastructureServices\Payment\CurrencyService(
                $c->get('domain.settings.service')
            )
        );
    }

    /**
     * @param $c
     * @return InfrastructureServices\Authentication\AbstractSocialAuthenticationService
     */
    public static function getSocialAuthenticationService($c)
    {
        return new InfrastructureServices\Authentication\StarterSocialAuthenticationService($c);
    }
}
