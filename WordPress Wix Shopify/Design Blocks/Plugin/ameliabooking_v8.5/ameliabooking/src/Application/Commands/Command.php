<?php

namespace AmeliaBooking\Application\Commands;

use AmeliaBooking\Application\Commands\Booking\Appointment\AddBookingCommand;
use AmeliaBooking\Application\Commands\Booking\Appointment\DeleteBookingRemotelyCommand;
use AmeliaBooking\Application\Commands\Booking\Appointment\SuccessfulBookingCommand;
use AmeliaBooking\Application\Commands\Coupon\GetValidCouponCommand;
use AmeliaBooking\Application\Commands\Google\FetchAccessTokenWithAuthCodeCommand;
use AmeliaBooking\Application\Commands\Google\GetGoogleAuthURLCommand;
use AmeliaBooking\Application\Commands\Mailchimp\FetchAccessTokenMailchimpCommand;
use AmeliaBooking\Application\Commands\Notification\GetSMSNotificationsHistoryCommand;
use AmeliaBooking\Application\Commands\Notification\UpdateSMSNotificationHistoryCommand;
use AmeliaBooking\Application\Commands\Notification\WhatsAppWebhookCommand;
use AmeliaBooking\Application\Commands\Notification\WhatsAppWebhookRegisterCommand;
use AmeliaBooking\Application\Commands\Outlook\FetchAccessTokenWithAuthCodeOutlookCommand;
use AmeliaBooking\Application\Commands\Payment\CalculatePaymentAmountCommand;
use AmeliaBooking\Application\Commands\Payment\PaymentCallbackCommand;
use AmeliaBooking\Application\Commands\Payment\PaymentLinkCommand;
use AmeliaBooking\Application\Commands\PaymentGateway\MolliePaymentCommand;
use AmeliaBooking\Application\Commands\PaymentGateway\MolliePaymentNotifyCommand;
use AmeliaBooking\Application\Commands\PaymentGateway\PayPalPaymentCallbackCommand;
use AmeliaBooking\Application\Commands\PaymentGateway\PayPalPaymentCommand;
use AmeliaBooking\Application\Commands\PaymentGateway\WooCommercePaymentCommand;
use AmeliaBooking\Application\Commands\PaymentGateway\RazorpayPaymentCommand;
use AmeliaBooking\Application\Commands\Square\DisconnectFromSquareAccountCommand;
use AmeliaBooking\Application\Commands\Square\FetchAccessTokenSquareCommand;
use AmeliaBooking\Application\Commands\Square\SquareRefundWebhookCommand;
use AmeliaBooking\Application\Commands\Square\SquarePaymentCommand;
use AmeliaBooking\Application\Commands\Stats\AddStatsCommand;
use AmeliaBooking\Application\Commands\User\Customer\ReauthorizeCommand;
use AmeliaBooking\Application\Commands\User\LoginCabinetCommand;
use AmeliaBooking\Application\Commands\User\LogoutCabinetCommand;
use AmeliaBooking\Application\Commands\User\SocialLoginCommand;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Services\Permissions\PermissionsService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use Slim\Http\Request;

/**
 * Class Command
 *
 * @package AmeliaBooking\Application\Commands
 */
abstract class Command
{
    protected $args;

    protected $container;

    private $fields = [];

    public $token;

    private $page;

    private $cabinetType;

    private $permissionService;

    private $userApplicationService;

    /**
     * Command constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        $this->args = $args;
        if (isset($args['type'])) {
            $this->setField('type', $args['type']);
        }
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $arg Argument to be fetched
     *
     * @return null|mixed
     */
    public function getArg($arg)
    {
        return isset($this->args[$arg]) ? $this->args[$arg] : null;
    }

    /**
     * @param $fieldName
     * @param $fieldValue
     */
    public function setField($fieldName, $fieldValue)
    {
        $this->fields[$fieldName] = $fieldValue;
    }

    /**
     * @param $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * Return a single field
     *
     * @param $fieldName
     *
     * @return mixed|null
     */
    public function getField($fieldName)
    {
        return isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : null;
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set Token
     *
     * @param Request $request
     */
    public function setToken($request)
    {
        $headers = $request->getHeaders();

        $token = null;

        /** @var SettingsService $settingsService */
        $settingsService = new SettingsService(new SettingsStorage());

        if (
            isset($headers['HTTP_AUTHORIZATION'][0]) &&
            ($values = explode(' ', $request->getHeaders()['HTTP_AUTHORIZATION'][0])) &&
            sizeof($values) === 2 &&
            $settingsService->getSetting('roles', 'enabledHttpAuthorization')
        ) {
            $token = $values[1];
        } elseif (isset($headers['HTTP_COOKIE'][0])) {
            foreach (explode('; ', $headers['HTTP_COOKIE'][0]) as $cookie) {
                if (($ameliaTokenCookie = explode('=', $cookie)) && $ameliaTokenCookie[0] === 'ameliaToken') {
                    $token = $ameliaTokenCookie[1];
                }
            }
        }

        $this->token = $token;
    }

    /**
     * Return Token
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set page
     *
     * @param string $page
     */
    public function setPage($page)
    {
        $this->page = explode('-', $page)[0];

        $this->cabinetType = !empty(explode('-', $page)[1]) ? explode('-', $page)[1] : null;
    }

    /**
     * Return page
     *
     * @return string|null
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param $request
     * @return string|null
     */
    public function validateNonce($request)
    {
        if (
            $request->getMethod() === 'POST' &&
            !self::getToken() &&
            !($this instanceof CalculatePaymentAmountCommand) &&
            !($this instanceof ReauthorizeCommand) &&
            !($this instanceof LoginCabinetCommand) &&
            !($this instanceof LogoutCabinetCommand) &&
            !($this instanceof AddBookingCommand) &&
            !($this instanceof AddStatsCommand) &&
            !($this instanceof DeleteBookingRemotelyCommand) &&
            !($this instanceof GetValidCouponCommand) &&
            !($this instanceof MolliePaymentCommand) &&
            !($this instanceof MolliePaymentNotifyCommand) &&
            !($this instanceof PayPalPaymentCommand) &&
            !($this instanceof PayPalPaymentCallbackCommand) &&
            !($this instanceof RazorpayPaymentCommand) &&
            !($this instanceof SquarePaymentCommand) &&
            !($this instanceof SquareRefundWebhookCommand) &&
            !($this instanceof DisconnectFromSquareAccountCommand) &&
            !($this instanceof WooCommercePaymentCommand) &&
            !($this instanceof PaymentCallbackCommand) &&
            !($this instanceof SuccessfulBookingCommand) &&
            !($this instanceof GetGoogleAuthURLCommand) &&
            !($this instanceof FetchAccessTokenWithAuthCodeOutlookCommand) &&
            !($this instanceof FetchAccessTokenWithAuthCodeCommand) &&
            !($this instanceof FetchAccessTokenMailchimpCommand) &&
            !($this instanceof FetchAccessTokenSquareCommand) &&
            !($this instanceof WhatsAppWebhookRegisterCommand) &&
            !($this instanceof WhatsAppWebhookCommand) &&
            !($this instanceof PaymentLinkCommand) &&
            !($this instanceof SocialLoginCommand) &&
            !($this instanceof GetSMSNotificationsHistoryCommand) &&
            !($this instanceof UpdateSMSNotificationHistoryCommand)
        ) {
            $queryParams = $request->getQueryParams();

            return wp_verify_nonce(
                !empty($queryParams['wpAmeliaNonce']) ? $queryParams['wpAmeliaNonce'] : $queryParams['ameliaNonce'],
                'ajax-nonce'
            );
        }

        return true;
    }

    /**
     * Return cabinet type
     *
     * @return string|null
     */
    public function getCabinetType()
    {
        return $this->cabinetType;
    }

    /**
     * @return PermissionsService
     */
    public function getPermissionService()
    {
        return $this->permissionService;
    }

    /**
     * @param PermissionsService $permissionService
     */
    public function setPermissionService($permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * @return UserApplicationService
     */
    public function getUserApplicationService()
    {
        return $this->userApplicationService;
    }

    /**
     * @param UserApplicationService $userApplicationService
     */
    public function setUserApplicationService($userApplicationService)
    {
        $this->userApplicationService = $userApplicationService;
    }
}
