<?php

/**
 * Assembling infrastructure services:
 * Instantiating infrastructure services
 */

use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Services\Notification\MailerFactory;
use AmeliaBooking\Infrastructure\Services\Notification\MailgunService;
use AmeliaBooking\Infrastructure\Services\Notification\OutlookService;
use AmeliaBooking\Infrastructure\Services\Notification\PHPMailService;
use AmeliaBooking\Infrastructure\Services\Notification\SMTPService;
use AmeliaBooking\Infrastructure\Services\Notification\WpMailService;

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Mailer Service
 *
 * @param Container $c
 *
 * @return MailgunService|PHPMailService|SMTPService|WpMailService|OutlookService
 */
$entries['infrastructure.mail.service'] = function ($c) {
    return MailerFactory::create($c);
};

/**
 * Report Service
 *
 * @return AmeliaBooking\Infrastructure\Services\Report\Spout\CsvService
 */
$entries['infrastructure.report.csv.service'] = function () {
    return new AmeliaBooking\Infrastructure\Services\Report\Spout\CsvService();
};

/**
 * PayPal Payment Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface
 */
$entries['infrastructure.payment.payPal.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getPayPalService($c);
};

/**
 * Stripe Payment Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface
 */
$entries['infrastructure.payment.stripe.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getStripeService($c);
};

/**
 * Mollie Payment Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface
 */
$entries['infrastructure.payment.mollie.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getMollieService($c);
};

/**
 * Razorpay Payment Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface
 */
$entries['infrastructure.payment.razorpay.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getRazorpayService($c);
};

/**
 * Square Payment Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface
 */
$entries['infrastructure.payment.square.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\Services\Payment\SquareService(
        $c->get('domain.settings.service'),
        new AmeliaBooking\Infrastructure\Services\Payment\CurrencyService(
            $c->get('domain.settings.service')
        )
    );
};

/**
 * Currency Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Payment\CurrencyService
 */
$entries['infrastructure.payment.currency.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\Services\Payment\CurrencyService(
        $c->get('domain.settings.service')
    );
};

/**
 * Less Parser Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Frontend\LessParserService
 */
$entries['infrastructure.frontend.lessParser.service'] = function ($c) {
    return new AmeliaBooking\Infrastructure\Services\Frontend\LessParserService(
        AMELIA_PATH . '/assets/less/frontend/amelia-booking.less',
        AMELIA_UPLOADS_PATH . '/amelia/css',
        $c->get('domain.settings.service')
    );
};

/**
 * Google Calendar Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Google\AbstractGoogleCalendarService
 */
$entries['infrastructure.google.calendar.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getCalendarGoogleService($c);
};

/**
 * Mailchimp Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Mailchimp\AbstractMailchimpService
 */
$entries['infrastructure.mailchimp.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getMailchimpService($c);
};

/**
 * Zoom Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Zoom\AbstractZoomService
 */
$entries['infrastructure.zoom.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getZoomService($c);
};

/**
 * Lesson Space Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\LessonSpace\AbstractLessonSpaceService
 */
$entries['infrastructure.lesson.space.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getLessonSpaceService($c);
};

/**
 * Outlook Calendar Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService
 */
$entries['infrastructure.outlook.calendar.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getCalendarOutlookService($c);
};

/**
 * Recaptcha Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Recaptcha\AbstractRecaptchaService
 */
$entries['infrastructure.recaptcha.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getRecaptchaService($c);
};

/**
 * Apple Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Apple\AbstractAppleCalendarService
 */

$entries['infrastructure.apple.calendar.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getAppleCalendarService($c);
};

/**
 * Social Authentication Service
 *
 * @param Container $c
 *
 * @return AmeliaBooking\Infrastructure\Services\Authentication\AbstractSocialAuthenticationService
 */

$entries['infrastructure.social.authentication.service'] = function ($c) {
    return AmeliaBooking\Infrastructure\Licence\InfrastructureService::getSocialAuthenticationService($c);
};
