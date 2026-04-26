<?php

namespace AmeliaBooking\Infrastructure\WP\SettingsService;

use AmeliaBooking\Application\Services\Location\AbstractCurrentLocation;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsStorageInterface;
use AmeliaBooking\Infrastructure\Licence;

/**
 * Class SettingsStorage
 *
 * @package AmeliaBooking\Infrastructure\WP\SettingsService
 */
class SettingsStorage implements SettingsStorageInterface
{
    /** @var array|mixed */
    private $settingsCache;

    /** @var AbstractCurrentLocation */
    private $locationService;

    private static $wpSettings = [
        'dateFormat'     => 'date_format',
        'timeFormat'     => 'time_format',
        'startOfWeek'    => 'start_of_week',
        'timeZoneString' => 'timezone_string',
        'gmtOffset'      => 'gmt_offset'
    ];

    /**
     * SettingsStorage constructor.
     */
    public function __construct()
    {
        $this->locationService = Licence\ApplicationService::getCurrentLocationService();

        $this->settingsCache = self::getSavedSettings();

        Licence\DataModifier::modifySettings($this->settingsCache);

        foreach (self::$wpSettings as $ameliaSetting => $wpSetting) {
            $this->settingsCache['wordpress'][$ameliaSetting] = get_option($wpSetting);
        }

        DateTimeService::setTimeZone($this->getAllSettings());
    }

    /**
     * @return array
     */
    private function getSavedSettings()
    {
        return json_decode(get_option('amelia_settings'), true);
    }

    /**
     * @param $settingCategoryKey
     * @param $settingKey
     *
     * @return mixed
     */
    public function getSetting($settingCategoryKey, $settingKey)
    {
        return isset($this->settingsCache[$settingCategoryKey][$settingKey]) ?
            $this->settingsCache[$settingCategoryKey][$settingKey] : null;
    }

    /**
     * @param $settingCategoryKey
     *
     * @return mixed
     */
    public function getCategorySettings($settingCategoryKey)
    {
        return isset($this->settingsCache[$settingCategoryKey]) ?
            $this->settingsCache[$settingCategoryKey] : null;
    }

    /**
     * @return array|mixed|null
     */
    public function getAllSettings()
    {
        $settings = [];

        if (null !== $this->settingsCache) {
            foreach ((array)$this->settingsCache as $settingsCategoryName => $settingsCategory) {
                if ($settingsCategoryName !== 'daysOff') {
                    foreach ((array)$settingsCategory as $settingName => $settingValue) {
                        $settings[$settingName] = $settingValue;
                    }
                }
            }

            return $settings;
        }

        return null;
    }

    /**
     * @return array|mixed|null
     */
    public function getAllSettingsCategorized()
    {
        return isset($this->settingsCache) ? $this->settingsCache : null;
    }

    /**
     * Return settings for frontend
     *
     * @return array|mixed
     */
    public function getFrontendSettings()
    {
        $phoneCountryCode = $this->getSetting('general', 'phoneDefaultCountryCode');
        $ipLocateApyKey   = $this->getSetting('general', 'ipLocateApiKey');

        $capabilities           = [];
        $additionalCapabilities = [];
        if (is_admin()) {
            $currentScreenId = get_current_screen()->id;
            $currentScreen   = substr($currentScreenId, strrpos($currentScreenId, '-') + 1);

            $capabilities = [
                'canRead'        => current_user_can('amelia_read_' . $currentScreen),
                'canReadOthers'  => current_user_can('amelia_read_others_' . $currentScreen),
                'canWrite'       => current_user_can('amelia_write_' . $currentScreen),
                'canWriteOthers' => current_user_can('amelia_write_others_' . $currentScreen),
                'canDelete'      => current_user_can('amelia_delete_' . $currentScreen),
                'canWriteStatus' => current_user_can('amelia_write_status_' . $currentScreen),
            ];

            $additionalCapabilities = [
                'canWriteCustomers' => current_user_can('amelia_write_customers'),
            ];
        }

        $wpUser = wp_get_current_user();

        $userType = 'customer';

        if (in_array('administrator', $wpUser->roles, true) || is_super_admin($wpUser->ID)) {
            $userType = 'admin';
        } elseif (in_array('wpamelia-manager', $wpUser->roles, true)) {
            $userType = 'manager';
        } elseif (in_array('wpamelia-provider', $wpUser->roles, true)) {
            $userType = 'provider';
        }

        return [
            'capabilities'           => $capabilities,
            'additionalCapabilities' => $additionalCapabilities,
            'daysOff'                => $this->getCategorySettings('daysOff'),
            'general'                => [
                'itemsPerPage'                           => $this->getSetting('general', 'itemsPerPage'),
                'itemsPerPageBackEnd'                    => $this->getSetting('general', 'itemsPerPageBackEnd'),
                'appointmentsPerPage'                    => $this->getSetting('general', 'appointmentsPerPage'),
                'eventsPerPage'                          => $this->getSetting('general', 'eventsPerPage'),
                'servicesPerPage'                        => $this->getSetting('general', 'servicesPerPage'),
                'customersFilterLimit'                   => $this->getSetting('general', 'customersFilterLimit'),
                'eventsFilterLimit'                      => $this->getSetting('general', 'eventsFilterLimit') ?: 1000,
                'calendarEmployeesPreselected'           => $this->getSetting('general', 'calendarEmployeesPreselected'),
                'phoneDefaultCountryCode'                => $phoneCountryCode === 'auto' ?
                    $this->locationService->getCurrentLocationCountryIso($ipLocateApyKey) : $phoneCountryCode,
                'timeSlotLength'                         => $this->getSetting('general', 'timeSlotLength'),
                'serviceDurationAsSlot'                  => $this->getSetting('general', 'serviceDurationAsSlot'),
                'defaultAppointmentStatus'               => $this->getSetting('general', 'defaultAppointmentStatus'),
                'gMapApiKey'                             => $this->getSetting('general', 'gMapApiKey'),
                'googleClientId'                         => $this->getSetting('googleCalendar', 'clientID'),
                'addToCalendar'                          => $this->getSetting('general', 'addToCalendar'),
                'requiredPhoneNumberField'               => $this->getSetting('general', 'requiredPhoneNumberField'),
                'requiredEmailField'                     => $this->getSetting('general', 'requiredEmailField'),
                'numberOfDaysAvailableForBooking'        => $this->getSetting('general', 'numberOfDaysAvailableForBooking'),
                'minimumTimeRequirementPriorToBooking'   =>
                    $this->getSetting('general', 'minimumTimeRequirementPriorToBooking'),
                'minimumTimeRequirementPriorToCanceling' =>
                    $this->getSetting('general', 'minimumTimeRequirementPriorToCanceling'),
                'minimumTimeRequirementPriorToRescheduling' =>
                    $this->getSetting('general', 'minimumTimeRequirementPriorToRescheduling'),
                'showClientTimeZone'                     => $this->getSetting('general', 'showClientTimeZone'),
                'redirectUrlAfterAppointment'            => $this->getSetting('general', 'redirectUrlAfterAppointment'),
                'customFieldsUploadsPath'                => $this->getSetting('general', 'customFieldsUploadsPath'),
                'customFieldsAllowedExtensions'          => $this->getSetting('general', 'customFieldsAllowedExtensions'),
                'runInstantPostBookingActions'           => $this->getSetting('general', 'runInstantPostBookingActions'),
                'sortingPackages'                        => $this->getSetting('general', 'sortingPackages'),
                'backLink'                               => $this->getSetting('general', 'backLink'),
                'sortingServices'                        => $this->getSetting('general', 'sortingServices'),
                'googleRecaptcha'                              => [
                    'enabled'   => $this->getSetting('general', 'googleRecaptcha')['enabled'],
                    'invisible' => $this->getSetting('general', 'googleRecaptcha')['invisible'],
                    'siteKey'   => $this->getSetting('general', 'googleRecaptcha')['siteKey'],
                ],
                'usedLanguages' => $this->getSetting('general', 'usedLanguages'),
            ],
            'googleMeet'             => [
                'enabled' => $this->getSetting('googleCalendar', 'enableGoogleMeet'),
            ],
            'microsoftTeams'         => [
                'enabled' => $this->getSetting('outlookCalendar', 'enableMicrosoftTeams'),
            ],
            'googleCalendar'         => [
              'enabled' =>
                  $this->getSetting('googleCalendar', 'clientID') &&
                  $this->getSetting('googleCalendar', 'clientSecret') &&
                  $this->getSetting('googleCalendar', 'calendarEnabled'),
              'googleMeetEnabled' => $this->getSetting('googleCalendar', 'enableGoogleMeet')
            ],
            'outlookCalendar'        => [
                'enabled'               =>
                    $this->getSetting('outlookCalendar', 'clientID') &&
                    $this->getSetting('outlookCalendar', 'clientSecret') &&
                    $this->getSetting('outlookCalendar', 'calendarEnabled'),
                'microsoftTeamsEnabled' => $this->getSetting('outlookCalendar', 'enableMicrosoftTeams'),
            ],
            'appleCalendar'          =>
                $this->getSetting('appleCalendar', 'clientID') && $this->getSetting('appleCalendar', 'clientSecret'),
            'zoom'                   => [
                'enabled' => (
                    $this->getSetting('zoom', 'enabled') &&
                    $this->getSetting('zoom', 'accountId') &&
                    $this->getSetting('zoom', 'clientId') &&
                    $this->getSetting('zoom', 'clientSecret')
                )
            ],
            'facebookPixel'          => $this->getCategorySettings('facebookPixel'),
            'googleAnalytics'        => $this->getCategorySettings('googleAnalytics'),
            'googleTag'              => $this->getCategorySettings('googleTag'),
            'mailchimp'              => [
                'subscribeFieldVisible' =>
                    !empty($this->getSetting('mailchimp', 'accessToken')) &&
                    !empty($this->getSetting('mailchimp', 'list')) &&
                    !empty($this->getSetting('mailchimp', 'server')),
                'checkedByDefault'      => $this->getSetting('mailchimp', 'checkedByDefault'),
            ],
            'lessonSpace'            => [
                'enabled' => $this->getSetting('lessonSpace', 'enabled') && $this->getSetting('lessonSpace', 'apiKey')
            ],
            'socialLogin'            => [
                'googleLoginEnabled'         => $this->getSetting('socialLogin', 'enableGoogleLogin'),
                'facebookLoginEnabled'       => $this->getSetting('socialLogin', 'enableFacebookLogin'),
                'facebookAppId'              => $this->getSetting('socialLogin', 'facebookAppId'),
                'facebookCredentialsEnabled' => $this->getSetting('socialLogin', 'facebookAppId') &&
                    $this->getSetting('socialLogin', 'facebookAppSecret'),
            ],
            'notifications'          => [
                'senderName'          => $this->getSetting('notifications', 'senderName'),
                'replyTo'             => $this->getSetting('notifications', 'replyTo'),
                'senderEmail'         => $this->getSetting('notifications', 'senderEmail'),
                'notifyCustomers'     => $this->getSetting('notifications', 'notifyCustomers'),
                'sendAllCF'           => $this->getSetting('notifications', 'sendAllCF'),
                'cancelSuccessUrl'    => $this->getSetting('notifications', 'cancelSuccessUrl'),
                'cancelErrorUrl'      => $this->getSetting('notifications', 'cancelErrorUrl'),
                'approveSuccessUrl'   => $this->getSetting('notifications', 'approveSuccessUrl'),
                'approveErrorUrl'     => $this->getSetting('notifications', 'approveErrorUrl'),
                'rejectSuccessUrl'    => $this->getSetting('notifications', 'rejectSuccessUrl'),
                'rejectErrorUrl'      => $this->getSetting('notifications', 'rejectErrorUrl'),
                'smsSignedIn'         => $this->getSetting('notifications', 'smsSignedIn'),
                'bccEmail'            => $this->getSetting('notifications', 'bccEmail'),
                'bccSms'              => $this->getSetting('notifications', 'bccSms'),
                'smsBalanceEmail'     => $this->getSetting('notifications', 'smsBalanceEmail'),
                'whatsAppPhoneID'     => $this->getSetting('notifications', 'whatsAppPhoneID'),
                'whatsAppAccessToken' => $this->getSetting('notifications', 'whatsAppAccessToken'),
                'whatsAppBusinessID'  => $this->getSetting('notifications', 'whatsAppBusinessID'),
                'whatsAppLanguage'    => $this->getSetting('notifications', 'whatsAppLanguage'),
                'whatsAppEnabled'     => $this->getSetting('notifications', 'whatsAppEnabled'),
            ],
            'payments'               => [
                'currency'                   => $this->getSetting('payments', 'symbol'),
                'currencyCode'               => $this->getSetting('payments', 'currency'),
                'priceSymbolPosition'        => $this->getSetting('payments', 'priceSymbolPosition'),
                'priceNumberOfDecimals'      => $this->getSetting('payments', 'priceNumberOfDecimals'),
                'priceSeparator'             => $this->getSetting('payments', 'priceSeparator'),
                'hideCurrencySymbolFrontend' => $this->getSetting('payments', 'hideCurrencySymbolFrontend'),
                'defaultPaymentMethod'       => $this->getSetting('payments', 'defaultPaymentMethod'),
                'onSite'                     => $this->getSetting('payments', 'onSite'),
                'couponsCaseInsensitive'     => $this->getSetting('payments', 'couponsCaseInsensitive'),
                'coupons'                    => $this->getSetting('payments', 'coupons'),
                'taxes'                      => $this->getSetting('payments', 'taxes'),
                'cart'                       => $this->getSetting('payments', 'cart'),
                'paymentLinks'               => [
                    'enabled'              => $this->getSetting('payments', 'paymentLinks')['enabled'],
                    'changeBookingStatus'  => $this->getSetting('payments', 'paymentLinks')['changeBookingStatus'],
                    'redirectUrl'          => $this->getSetting('payments', 'paymentLinks')['redirectUrl']
                ],
                'payPal'                     => [
                    'enabled'         => $this->getSetting('payments', 'payPal')['enabled'],
                    'sandboxMode'     => $this->getSetting('payments', 'payPal')['sandboxMode'],
                    'testApiClientId' => $this->getSetting('payments', 'payPal')['testApiClientId'],
                    'liveApiClientId' => $this->getSetting('payments', 'payPal')['liveApiClientId'],
                ],
                'stripe'                     => [
                    'enabled'            => $this->getSetting('payments', 'stripe')['enabled'],
                    'testMode'           => $this->getSetting('payments', 'stripe')['testMode'],
                    'livePublishableKey' => $this->getSetting('payments', 'stripe')['livePublishableKey'],
                    'testPublishableKey' => $this->getSetting('payments', 'stripe')['testPublishableKey'],
                    'connect'            => $this->getSetting('payments', 'stripe')['connect'],
                    'address'            => $this->getSetting('payments', 'stripe')['address'],
                ],
                'wc'                         => [
                    'enabled'      => $this->getSetting('payments', 'wc')['enabled'],
                    'productId'    => $this->getSetting('payments', 'wc')['productId'],
                    'page'         => $this->getSetting('payments', 'wc')['page'],
                    'onSiteIfFree' => $this->getSetting('payments', 'wc')['onSiteIfFree']
                ],
                'mollie'                     => [
                    'enabled'   => $this->getSetting('payments', 'mollie')['enabled'],
                    'cancelBooking'   => $this->getSetting('payments', 'mollie')['cancelBooking'],
                ],
                'square'                     => [
                    'enabled'        => $this->getSetting('payments', 'square')['enabled'],
                    'countryCode'    => $this->getSetting('payments', 'square')['countryCode'],
                    'clientLiveId'   => $this->getSetting('payments', 'square')['clientLiveId'],
                    'clientTestId'   => $this->getSetting('payments', 'square')['clientTestId'],
                    'testMode'       => $this->getSetting('payments', 'square')['testMode'],
                    'accessTokenSet' =>
                        !empty($this->getSetting('payments', 'square')['accessToken']) &&
                        !empty($this->getSetting('payments', 'square')['accessToken']['access_token']),
                    'locationId'     => $this->getSetting('payments', 'square')['locationId']
                ],
                'razorpay'                     => [
                    'enabled'   => $this->getSetting('payments', 'razorpay')['enabled'],
                ],
            ],
            'role'                   => $userType,
            'weekSchedule'           => $this->getCategorySettings('weekSchedule'),
            'wordpress'              => [
                'dateFormat'  => $this->getSetting('wordpress', 'dateFormat'),
                'timeFormat'  => $this->getSetting('wordpress', 'timeFormat'),
                'startOfWeek' => (int)$this->getSetting('wordpress', 'startOfWeek'),
                'timezone'    => $this->getSetting('wordpress', 'timeZoneString'),
                'locale'      => AMELIA_LOCALE
            ],
            'labels'                 => [
                'enabled' => $this->getSetting('labels', 'enabled')
            ],
            'activation'             => [
                'showAmeliaSurvey'              => $this->getSetting('activation', 'showAmeliaSurvey'),
                'showAmeliaPromoCustomizePopup' => $this->getSetting('activation', 'showAmeliaPromoCustomizePopup'),
                'showActivationSettings'        => $this->getSetting('activation', 'showActivationSettings'),
                'stash'                         => $this->getSetting('activation', 'stash'),
                'disableUrlParams'              => $this->getSetting('activation', 'disableUrlParams'),
                'isNewInstallation'             => $this->getSetting('activation', 'isNewInstallation'),
                'hideUnavailableFeatures'       => $this->getSetting('activation', 'hideUnavailableFeatures'),
                'premiumBannerVisibility'       => $this->getSetting('activation', 'premiumBannerVisibility'),
                'dismissibleBannerVisibility'   => $this->getSetting('activation', 'dismissibleBannerVisibility'),
            ],
            'roles'                  => [
                'allowAdminBookAtAnyTime'     => $this->getSetting('roles', 'allowAdminBookAtAnyTime'),
                'allowAdminBookOverApp'       => $this->getSetting('roles', 'allowAdminBookOverApp'),
                'adminServiceDurationAsSlot'  => $this->getSetting('roles', 'adminServiceDurationAsSlot'),
                'allowConfigureSchedule'      => $this->getSetting('roles', 'allowConfigureSchedule'),
                'allowConfigureDaysOff'       => $this->getSetting('roles', 'allowConfigureDaysOff'),
                'allowConfigureSpecialDays'   => $this->getSetting('roles', 'allowConfigureSpecialDays'),
                'allowConfigureServices'      => $this->getSetting('roles', 'allowConfigureServices'),
                'allowWriteAppointments'      => $this->getSetting('roles', 'allowWriteAppointments'),
                'allowWriteCustomers'         => $this->getSetting('roles', 'allowWriteCustomers'),
                'automaticallyCreateCustomer' => $this->getSetting('roles', 'automaticallyCreateCustomer'),
                'inspectCustomerInfo'         => $this->getSetting('roles', 'inspectCustomerInfo'),
                'allowCustomerReschedule'     => $this->getSetting('roles', 'allowCustomerReschedule'),
                'allowCustomerCancelPackages' => $this->getSetting('roles', 'allowCustomerCancelPackages'),
                'allowCustomerDeleteProfile'  => $this->getSetting('roles', 'allowCustomerDeleteProfile'),
                'allowWriteEvents'            => $this->getSetting('roles', 'allowWriteEvents'),
                'customerCabinet'             => [
                    'enabled'         => $this->getSetting('roles', 'customerCabinet')['enabled'],
                    'loginEnabled'    => $this->getSetting('roles', 'customerCabinet')['loginEnabled'],
                    'tokenValidTime'  => $this->getSetting('roles', 'customerCabinet')['tokenValidTime'],
                    'pageUrl'         => $this->getSetting('roles', 'customerCabinet')['pageUrl'],
                    'googleRecaptcha' => $this->getSetting('roles', 'customerCabinet')['googleRecaptcha'] &&
                        $this->getSetting('general', 'googleRecaptcha')['siteKey'] &&
                        $this->getSetting('general', 'googleRecaptcha')['secret'],
                ],
                'providerCabinet'             => [
                    'enabled'         => $this->getSetting('roles', 'providerCabinet')['enabled'],
                    'loginEnabled'    => $this->getSetting('roles', 'providerCabinet')['loginEnabled'],
                    'tokenValidTime'  => $this->getSetting('roles', 'providerCabinet')['tokenValidTime'],
                    'googleRecaptcha' => $this->getSetting('roles', 'providerCabinet')['googleRecaptcha'] &&
                        $this->getSetting('general', 'googleRecaptcha')['siteKey'] &&
                        $this->getSetting('general', 'googleRecaptcha')['secret'],
                ],
                'providerBadges'          => $this->getSetting('roles', 'providerBadges'),
                'enableNoShowTag'         => $this->getSetting('roles', 'enableNoShowTag'),
                'limitPerCustomerService' => $this->getSetting('roles', 'limitPerCustomerService'),
                'limitPerCustomerPackage' => $this->getSetting('roles', 'limitPerCustomerPackage'),
                'limitPerCustomerEvent'   => $this->getSetting('roles', 'limitPerCustomerEvent'),
                'limitPerEmployee'        => $this->getSetting('roles', 'limitPerEmployee'),
            ],
            'customization'          => $this->getCategorySettings('customization'),
            'customizedData'         => $this->getCategorySettings('customizedData'),
            'appointments'           => $this->getCategorySettings('appointments'),
            'slotDateConstraints'    => [
                'minDate' => DateTimeService::getNowDateTimeObject()
                    ->modify("+{$this->getSetting('general', 'minimumTimeRequirementPriorToBooking')} seconds")
                    ->format('Y-m-d H:i:s'),
                'maxDate' => DateTimeService::getNowDateTimeObject()
                    ->modify("+{$this->getSetting('general', 'numberOfDaysAvailableForBooking')} day")
                    ->format('Y-m-d H:i:s')
            ],
            'company'                => [
                'email' => $this->getSetting('company', 'email'),
                'phone' => $this->getSetting('company', 'phone'),
            ]
        ];
    }

    /**
     * @param $settingCategoryKey
     * @param $settingKey
     * @param $settingValue
     *
     * @return mixed|void
     */
    public function setSetting($settingCategoryKey, $settingKey, $settingValue)
    {
        $this->settingsCache[$settingCategoryKey][$settingKey] = $settingValue;
        $settingsCopy = $this->settingsCache;

        unset($settingsCopy['wordpress']);
        update_option('amelia_settings', json_encode($settingsCopy));
    }

    /**
     * @param $settingCategoryKey
     * @param $settingValues
     *
     * @return mixed|void
     */
    public function setCategorySettings($settingCategoryKey, $settingValues)
    {
        $this->settingsCache[$settingCategoryKey] = $settingValues;
        $settingsCopy = $this->settingsCache;

        unset($settingsCopy['wordpress']);
        update_option('amelia_settings', json_encode($settingsCopy));
    }

    /**
     * @param array $settings
     *
     * @return mixed|void
     */
    public function setAllSettings($settings)
    {
        foreach ($settings as $settingCategoryKey => $settingValues) {
            $this->settingsCache[$settingCategoryKey] = $settingValues;
        }
        $settingsCopy = $this->settingsCache;

        Licence\DataModifier::restoreSettings($settingsCopy, self::getSavedSettings());

        if (get_option('amelia_show_wpdt_promo') === false) {
            update_option('amelia_show_wpdt_promo', 'yes');
        }

        unset($settingsCopy['wordpress']);
        update_option('amelia_settings', json_encode($settingsCopy));
    }
}
