<?php

/**
 * Settings hook for activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Services\Frontend\LessParserService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use Exception;

/**
 * Class ActivationSettingsHook
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class ActivationSettingsHook
{
    /**
     * Initialize the plugin
     *
     * @throws Exception
     */
    public static function init()
    {
        self::initDBSettings();

        self::initGeneralSettings();

        self::initCompanySettings();

        self::initNotificationsSettings();

        self::initDaysOffSettings();

        self::initWeekScheduleSettings();

        self::initGoogleCalendarSettings();

        self::initOutlookCalendarSettings();

        self::initPaymentsSettings();

        self::initActivationSettings();

        self::initCustomizationSettings();

        self::initLabelsSettings();

        self::initRolesSettings();

        self::initAppointmentsSettings();

        self::initWebHooksSettings();

        self::initZoomSettings();

        self::initApiKeySettings();

        self::initLessonSpaceSettings();

        self::initIcsSettings();

        self::initFacebookPixelSettings();

        self::initGoogleAnalyticsSettings();

        self::initGoogleTagSettings();

        self::initMailchimpSettings();

        self::initAppleCalendarSettings();

        self::initSocialLoginSettings();
    }

    /**
     * @param string $category
     * @param array  $settings
     * @param bool   $replace
     */
    public static function initSettings($category, $settings, $replace = false)
    {
        $settingsService = new SettingsService(new SettingsStorage());

        if (!$settingsService->getCategorySettings($category)) {
            $settingsService->setCategorySettings(
                $category,
                []
            );
        }

        foreach ($settings as $key => $value) {
            if ($replace || null === $settingsService->getSetting($category, $key)) {
                $settingsService->setSetting(
                    $category,
                    $key,
                    $value
                );
            }
        }
    }

    /**
     * Init General Settings
     *
     * @param array $savedSettings
     *
     * @return array
     */
    public static function getDefaultGeneralSettings($savedSettings)
    {
        return [
            'timeSlotLength'                         => 1800,
            'serviceDurationAsSlot'                  => false,
            'bufferTimeInSlot'                       => true,
            'defaultAppointmentStatus'               => 'approved',
            'defaultEventStatus'                     => 'approved',
            'minimumTimeRequirementPriorToBooking'   => 0,
            'minimumTimeRequirementPriorToCanceling' => 0,
            'minimumTimeRequirementPriorToRescheduling' =>
                isset($savedSettings['minimumTimeRequirementPriorToCanceling']) &&
                !isset($savedSettings['minimumTimeRequirementPriorToRescheduling']) ?
                    $savedSettings['minimumTimeRequirementPriorToCanceling'] : 0,
            'numberOfDaysAvailableForBooking'        => SettingsService::NUMBER_OF_DAYS_AVAILABLE_FOR_BOOKING,
            'backendSlotsDaysInFuture'               => SettingsService::NUMBER_OF_DAYS_AVAILABLE_FOR_BOOKING,
            'backendSlotsDaysInPast'                 => SettingsService::NUMBER_OF_DAYS_AVAILABLE_FOR_BOOKING,
            'phoneDefaultCountryCode'                => 'auto',
            'ipLocateApiKey'                         => '',
            'requiredPhoneNumberField'               => false,
            'requiredEmailField'                     => true,
            'itemsPerPage'                           => 12,
            'itemsPerPageBackEnd'                    => 30,
            'appointmentsPerPage'                    => 100,
            'eventsPerPage'                          => 100,
            'servicesPerPage'                        => 100,
            'customersFilterLimit'                   => 100,
            'eventsFilterLimit'                      => 1000,
            'calendarEmployeesPreselected'           => 0,
            'gMapApiKey'                             => '',
            'addToCalendar'                          => true,
            'defaultPageOnBackend'                   => 'Dashboard',
            'showClientTimeZone'                     => false,
            'redirectUrlAfterAppointment'            => '',
            'customFieldsUploadsPath'                => '',
            'customFieldsAllowedExtensions'          => [
                '.jpg'  => 'image/jpeg',
                '.jpeg' => 'image/jpeg',
                '.png'  => 'image/png',
                '.mp3'  => 'audio/mpeg',
                '.mpeg' => 'video/mpeg',
                '.mp4'  => 'video/mp4',
                '.txt'  => 'text/plain',
                '.csv'  => 'text/plain',
                '.xls'  => 'application/vnd.ms-excel',
                '.pdf'  => 'application/pdf',
                '.doc'  => 'application/msword',
                '.docx' => 'application/msword'
            ],
            'runInstantPostBookingActions'           => false,
            'sortingPackages'                        => 'nameAsc',
            'sortingServices'                        => 'nameAsc',
            'calendarLocaleSubstitutes'              => [
            ],
            'googleRecaptcha'                        => [
                'enabled'   => false,
                'invisible' => true,
                'siteKey'   => '',
                'secret'    => '',
            ],
            'usedLanguages'                          => [],
        ];
    }

    /**
     * Get General Settings
     */
    private static function initGeneralSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('general');

        $settings = self::getDefaultGeneralSettings($savedSettings);

        $settings['backLink'] = self::getBackLinkSetting();

        self::initSettings('general', $settings);

        self::setNewSettingsToExistingSettings(
            'general',
            [
                ['backLink', 'url'],
            ],
            $settings
        );
    }

    /**
     * Init DB Settings
     */
    private static function initDBSettings()
    {
        $settings = [
            'mysqliEnabled'      => false,
            'pdoEmulatePrepares' => false,
            'pdoBigSelect'       => true,
            'ssl' => [
                'enable'      => false,
                'key'         => null,
                'cert'        => null,
                'ca'          => null,
                'verify_cert' => null,
            ],
            'wpTablesPrefix'     => '',
            'port'               => 3306
        ];

        self::initSettings('db', $settings);
    }

    /**
     * Init Company Settings
     */
    private static function initCompanySettings()
    {

        $settings = [
            'pictureFullPath'  => '',
            'pictureThumbPath' => '',
            'name'             => '',
            'address'          => '',
            'phone'            => '',
            'vat'              => '',
            'countryPhoneIso'  => '',
            'website'          => '',
            'translations'     => '',
        ];

        self::initSettings('company', $settings);
    }

    /**
     * Get Notification Settings
     *
     * @param array $savedSettings
     *
     * @return array
     */
    public static function getDefaultNotificationsSettings($savedSettings)
    {
        return [
            'mailService'          => 'php',
            'smtpHost'             => '',
            'smtpPort'             => '',
            'smtpSecure'           => 'ssl',
            'smtpUsername'         => '',
            'smtpPassword'         => '',
            'mailgunApiKey'        => '',
            'mailgunDomain'        => '',
            'mailgunEndpoint'      => '',
            'senderName'           => '',
            'replyTo'              => '',
            'senderEmail'          => '',
            'notifyCustomers'      => true,
            'sendAllCF'            => true,
            'smsAlphaSenderId'     => 'Amelia',
            'smsSignedIn'          => false,
            'smsApiToken'          => '',
            'bccEmail'             => '',
            'bccSms'               => '',
            'emptyPackageEmployees' => '',
            'smsBalanceEmail'      => ['enabled' => false, 'minimum' => 0, 'email' => ''],
            'cancelSuccessUrl'     => '',
            'cancelErrorUrl'       => '',
            'approveSuccessUrl'    => '',
            'approveErrorUrl'      => '',
            'rejectSuccessUrl'     => '',
            'rejectErrorUrl'       => '',
            'breakReplacement'     => '<br>',
            'pendingReminder'      => false,
            'sendInvoice'          => false,
            'whatsAppEnabled'      =>
                $savedSettings &&
                !empty($savedSettings['whatsAppPhoneID']) &&
                !empty($savedSettings['whatsAppAccessToken']) &&
                !empty($savedSettings['whatsAppBusinessID']),
            'whatsAppPhoneID'      => '',
            'whatsAppAccessToken'  => '',
            'whatsAppBusinessID'   => '',
            'whatsAppLanguage'     => '',
            'whatsAppReplyEnabled' => false,
            'whatsAppReplyMsg'     => 'Dear %customer_full_name%,
This message does not have an option for responding. If you need additional information about your booking, please contact us at %company_phone%',
            'whatsAppReplyToken'   => (new Token(null, 20))->getValue(),
        ];
    }

    /**
     * Init Notification Settings
     */
    private static function initNotificationsSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('notifications');

        $settings = self::getDefaultNotificationsSettings($savedSettings);

        self::initSettings('notifications', $settings);
    }

    /**
     * Init Days Off Settings
     */
    private static function initDaysOffSettings()
    {
        self::initSettings('daysOff', []);
    }

    /**
     * Init Work Schedule Settings
     */
    private static function initWeekScheduleSettings()
    {
        self::initSettings(
            'weekSchedule',
            [
                [
                    'day'     => 'Monday',
                    'time'    => ['09:00', '17:00'],
                    'breaks'  => [],
                    'periods' => []
                ],
                [
                    'day'     => 'Tuesday',
                    'time'    => ['09:00', '17:00'],
                    'breaks'  => [],
                    'periods' => []
                ],
                [
                    'day'     => 'Wednesday',
                    'time'    => ['09:00', '17:00'],
                    'breaks'  => [],
                    'periods' => []
                ],
                [
                    'day'     => 'Thursday',
                    'time'    => ['09:00', '17:00'],
                    'breaks'  => [],
                    'periods' => []
                ],
                [
                    'day'     => 'Friday',
                    'time'    => ['09:00', '17:00'],
                    'breaks'  => [],
                    'periods' => []
                ],
                [
                    'day'     => 'Saturday',
                    'time'    => [],
                    'breaks'  => [],
                    'periods' => []
                ],
                [
                    'day'     => 'Sunday',
                    'time'    => [],
                    'breaks'  => [],
                    'periods' => []
                ]
            ]
        );
    }

    /**
     * Init Google Calendar Settings
     */
    private static function initGoogleCalendarSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('googleCalendar');

        $settings = [
            'clientID'                        => '',
            'clientSecret'                    => '',
            'redirectURI'                     => AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-employees',
            'showAttendees'                   => false,
            'insertPendingAppointments'       => false,
            'addAttendees'                    => false,
            'sendEventInvitationEmail'        => false,
            'removeGoogleCalendarBusySlots'   => false,
            'maximumNumberOfEventsReturned'   => 50,
            'eventTitle'                      => '%service_name%',
            'eventDescription'                => '',
            'includeBufferTimeGoogleCalendar' => false,
            'status'                          => 'tentative',
            'enableGoogleMeet'                => false,
            'title'                           => [
                'appointment' => $savedSettings && !empty($savedSettings['eventTitle']) ?
                    $savedSettings['eventTitle'] : '%service_name%',
                'event' => '%event_name%'
            ],
            'description'                      => [
                'appointment' => $savedSettings && !empty($savedSettings['eventDescription']) ?
                    $savedSettings['eventDescription'] : '',
                'event' => ''
            ],
            'calendarEnabled'                  => !empty($savedSettings['clientID']) && !empty($savedSettings['clientSecret']),
        ];

        self::initSettings('googleCalendar', $settings);
    }

    /**
     * Init Outlook Calendar Settings
     */
    private static function initOutlookCalendarSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('outlookCalendar');

        $settings = [
            'clientID'                         => '',
            'clientSecret'                     => '',
            'redirectURI'                      => AMELIA_SITE_URL . '/wp-admin/',
            'insertPendingAppointments'        => false,
            'addAttendees'                     => false,
            'sendEventInvitationEmail'         => false,
            'removeOutlookCalendarBusySlots'   => false,
            'maximumNumberOfEventsReturned'    => 50,
            'eventTitle'                       => '%service_name%',
            'eventDescription'                 => '',
            'includeBufferTimeOutlookCalendar' => false,
            'enableMicrosoftTeams'             => false,
            'title'                            => [
                'appointment' => $savedSettings && !empty($savedSettings['eventTitle']) ?
                    $savedSettings['eventTitle'] : '%service_name%',
                'event' => '%event_name%'
            ],
            'description'                      => [
                'appointment' => $savedSettings && !empty($savedSettings['eventDescription']) ?
                    $savedSettings['eventDescription'] : '',
                'event' => ''
            ],
            'calendarEnabled'                  => !empty($savedSettings['clientID']) && !empty($savedSettings['clientSecret']),
            'mailEnabled'                      => false,
            'token'                            => null,
        ];

        self::initSettings('outlookCalendar', $settings);
    }

    /**
     * Init Apple Calendar Settings
     */
    private static function initAppleCalendarSettings()
    {
        $settings = [
            'clientID'                        => '',
            'clientSecret'                    => '',
            'redirectURI'                     => AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-employees',
            'insertPendingAppointments'       => false,
            'addAttendees'                    => false,
            'removeAppleCalendarBusySlots'    => false,
            'eventTitle'                      => '%service_name%',
            'eventDescription'                => '',
            'includeBufferTimeAppleCalendar' => false,
            'title'                           => [
                'appointment' => '%service_name%',
                'event' => '%event_name%'
            ],
            'description'                      => [
                'appointment' => '',
                'event' => ''
            ],
        ];

        self::initSettings('appleCalendar', $settings);
    }

    /**
     * Init Zoom Settings
     */
    private static function initZoomSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('zoom');

        $settings = [
            'enabled'                     => true,
            'apiKey'                      => '',
            'apiSecret'                   => '',
            'meetingTitle'                => '%reservation_name%',
            'meetingAgenda'               => '%reservation_description%',
            'pendingAppointmentsMeetings' => false,
            's2sEnabled'                  => true,
            'accountId'                   => '',
            'clientId'                    => '',
            'clientSecret'                => '',
            'accessToken'                 => '',
        ];

        self::initSettings('zoom', $settings);
    }


    /**
     * Init Api Key Settings
     */
    private static function initApiKeySettings()
    {
        $settings = [
            'apiKeys' => []
        ];

        self::initSettings('apiKeys', $settings);
    }

    /**
     * Init Lesson Space Settings
     */
    private static function initLessonSpaceSettings()
    {
        $settings = [
            'enabled'                     => true,
            'apiKey'                      => '',
            'spaceNameAppointments'       => '%reservation_name%',
            'spaceNameEvents'             => '%reservation_name%',
            'pendingAppointments'         => false,
            'companyId'                   => ''
        ];

        self::initSettings('lessonSpace', $settings);
    }

    /**
     * Init FacebookPixel Settings
     */
    private static function initFacebookPixelSettings()
    {
        $settings = [
            'id'               => '',
            'tracking' => [
                'appointment' => [],
                'event'       => [],
                'package'     => [],
            ],
        ];

        self::initSettings('facebookPixel', $settings);
    }

    /**
     * Init Google Analytics Settings
     */
    private static function initGoogleAnalyticsSettings()
    {
        $settings = [
            'id'               => '',
            'tracking' => [
                'appointment' => [],
                'event'       => [],
                'package'     => [],
            ],
        ];

        self::initSettings('googleAnalytics', $settings);
    }

    /**
     * Init GoogleTag Settings
     */
    private static function initGoogleTagSettings()
    {
        $settings = [
            'id'               => '',
            'tracking' => [
                'appointment' => [],
                'event'       => [],
                'package'     => [],
            ],
        ];

        self::initSettings('googleTag', $settings);
    }


    /**
     * Init Mailchimp Settings
     */
    private static function initMailchimpSettings()
    {
        $settings = [
            'accessToken'      => null,
            'server'           => null,
            'list'             => null,
            'checkedByDefault' => false
        ];

        self::initSettings('mailchimp', $settings);
    }

    /**
     * Init Ics Settings
     */
    private static function initIcsSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('general');

        $settings = [
            'sendIcsAttachment'  => isset($savedSettings['sendIcsAttachment']) ? $savedSettings['sendIcsAttachment'] : false,
            'sendIcsAttachmentPending'  => false,
            'description'        => [
                'appointment'  => '',
                'event'        => '',
                'translations' => [
                    'appointment' => null,
                    'event'       => null,
                ],
            ],
        ];

        self::initSettings('ics', $settings);
    }

    /**
     * Get Payments Settings
     *
     * @param array $savedSettings
     *
     * @return array
     */
    public static function getDefaultPaymentsSettings($savedSettings)
    {
        return [
            'currency'                   => 'USD',
            'symbol'                     => '$',
            'priceSymbolPosition'        => 'before',
            'priceNumberOfDecimals'      => 2,
            'priceSeparator'             => 1,
            'hideCurrencySymbolFrontend' => false,
            'defaultPaymentMethod'       => 'onSite',
            'onSite'                     => true,
            'cart'                       => false,
            'coupons'                    => true,
            'couponsCaseInsensitive'     => false,
            'paymentLinks'               => [
                'enabled'              => false,
                'changeBookingStatus'  => false,
                'redirectUrl'          => AMELIA_SITE_URL
            ],
            'taxes'                      => [
                'enabled'  => false,
                'excluded' => true,
            ],
            'payPal'                     => [
                'enabled'         => false,
                'sandboxMode'     => false,
                'liveApiClientId' => '',
                'liveApiSecret'   => '',
                'testApiClientId' => '',
                'testApiSecret'   => '',
                'description'     => [
                    'enabled'     => false,
                    'appointment' => '',
                    'package'     => '',
                    'event'       => '',
                    'cart'        => '',
                ],
            ],
            'stripe'                     => [
                'enabled'            => false,
                'testMode'           => false,
                'livePublishableKey' => '',
                'liveSecretKey'      => '',
                'testPublishableKey' => '',
                'testSecretKey'      => '',
                'address'            => false,
                'description'        => [
                    'enabled'     => false,
                    'appointment' => '',
                    'package'     => '',
                    'event'       => '',
                    'cart'        => '',
                ],
                'metaData'           => [
                    'enabled'     => false,
                    'appointment' => null,
                    'package'     => null,
                    'event'       => null,
                    'cart'        => '',
                ],
                'manualCapture'   => false,
                'returnUrl'       => '',
                'connect'         => [
                    'enabled'      => false,
                    'method'       => 'transfer',
                    'amount'       => 0,
                    'type'         => 'percentage',
                    'capabilities' => ['card_payments', 'transfers'],
                ],
            ],
            'wc'                         => [
                'enabled'      => false,
                'productId'    => '',
                'onSiteIfFree' => false,
                'page'         => 'cart',
                'dashboard'    => true,
                'checkoutData' => [
                    'appointment' => '',
                    'package'     => '',
                    'event'       => '',
                    'cart'        => '',
                    'translations' => [
                        'appointment' => null,
                        'event'       => null,
                        'package'     => null,
                        'cart'        => '',
                    ],
                ],
                'skipCheckoutGetValueProcessing' => isset($savedSettings['wc']['skipCheckoutGetValueProcessing']) ?
                    $savedSettings['wc']['skipCheckoutGetValueProcessing'] : true,
                'skipGetItemDataProcessing'      => !isset($savedSettings['wc']),
                'redirectPage' => 1,
                'bookMultiple' => false,
                'bookUnpaid'   => empty($savedSettings['wc']),
                'rules'        =>
                    isset($savedSettings['wc']['rules']) ? $savedSettings['wc']['rules'] : [
                    'appointment' => [
                        [
                            'order'   => 'pending',
                            'booking' => 'pending',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'on-hold',
                            'booking' => 'default',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'processing',
                            'booking' => 'default',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'completed',
                            'booking' => 'default',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'cancelled',
                            'booking' => 'canceled',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'failed',
                            'booking' => 'canceled',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                    ],
                    'package'     => [
                        [
                            'order'   => 'on-hold',
                            'booking' => 'approved',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'processing',
                            'booking' => 'approved',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'completed',
                            'booking' => 'approved',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'cancelled',
                            'booking' => 'canceled',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'failed',
                            'booking' => 'canceled',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                    ],
                    'event'       => [
                        [
                            'order'   => 'pending',
                            'booking' => 'pending',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'on-hold',
                            'booking' => 'approved',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'processing',
                            'booking' => 'approved',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'completed',
                            'booking' => 'approved',
                            'payment' => 'paid',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'cancelled',
                            'booking' => 'canceled',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                        [
                            'order'   => 'failed',
                            'booking' => 'canceled',
                            'payment' => 'pending',
                            'update'  => false,
                        ],
                    ],
                ],
            ],
            'mollie'           => [
                'enabled'         => false,
                'testMode'        => false,
                'liveApiKey'      => '',
                'testApiKey'      => '',
                'description'        => [
                    'enabled'     => false,
                    'appointment' => '',
                    'package'     => '',
                    'event'       => '',
                    'cart'        => '',
                ],
                'metaData'           => [
                    'enabled'     => false,
                    'appointment' => null,
                    'package'     => null,
                    'event'       => null,
                    'cart'        => '',
                ],
                'method'          => [],
                'cancelBooking'   => false
            ],
            'razorpay'         => [
                'enabled'         => false,
                'testMode'        => false,
                'liveKeyId'       => '',
                'liveKeySecret'   => '',
                'testKeyId'       => '',
                'testKeySecret'   => '',
                'description'     => [
                    'enabled'       => false,
                    'appointment'   => '',
                    'package'       => '',
                    'event'         => '',
                    'cart'          => '',
                ],
                'name'            => [
                    'enabled'       => false,
                    'appointment'   => '',
                    'package'       => '',
                    'event'         => '',
                    'cart'          => '',
                ],
                'metaData'       => [
                    'enabled'       => false,
                    'appointment'   => null,
                    'package'       => null,
                    'event'         => null,
                    'cart'          => '',
                ],
            ],
            'square'               => [
                'enabled'            => false,
                'locationId'         => '',
                'accessToken'        => '',
                'testMode'           => false,
                'clientLiveId'       => 'sq0idp-TtDyGP_2RfKYpFzrDqs0lw',
                'clientTestId'       => 'sandbox-sq0idb-Wxnxasx1NMG_ZyvM--JV4Q',
                'countryCode'        => '',
                'description'        => [
                    'enabled'     => false,
                    'appointment' => '',
                    'package'     => '',
                    'event'       => '',
                    'cart'        => ''
                ],
                'metaData'           => [
                    'enabled'     => false,
                    'appointment' => null,
                    'package'     => null,
                    'event'       => null,
                    'cart'        => null
                ],
            ],
        ];
    }

    /**
     * Init Payments Settings
     */
    private static function initPaymentsSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('payments');

        $settings = self::getDefaultPaymentsSettings($savedSettings);

        self::initSettings('payments', $settings);

        self::setNewSettingsToExistingSettings(
            'payments',
            [
                ['stripe', 'connect'],
                ['stripe', 'connect', 'capabilities'],
                ['stripe', 'description'],
                ['stripe', 'description', 'package'],
                ['stripe', 'description', 'cart'],
                ['stripe', 'metaData'],
                ['stripe', 'metaData', 'package'],
                ['stripe', 'metaData', 'cart'],
                ['stripe', 'manualCapture'],
                ['stripe', 'returnUrl'],
                ['stripe', 'address'],
                ['payPal', 'description'],
                ['payPal', 'description', 'package'],
                ['payPal', 'description', 'cart'],
                ['wc', 'onSiteIfFree'],
                ['wc', 'page'],
                ['wc', 'dashboard'],
                ['wc', 'skipCheckoutGetValueProcessing'],
                ['wc', 'skipGetItemDataProcessing'],
                ['wc', 'rules'],
                ['wc', 'redirectPage'],
                ['wc', 'bookMultiple'],
                ['wc', 'bookUnpaid'],
                ['wc', 'checkoutData'],
                ['wc', 'checkoutData', 'package'],
                ['wc', 'checkoutData', 'cart'],
                ['wc', 'checkoutData', 'translations'],
                ['wc', 'checkoutData', 'translations', 'appointment'],
                ['wc', 'checkoutData', 'translations', 'event'],
                ['wc', 'checkoutData', 'translations', 'package'],
                ['wc', 'checkoutData', 'translations', 'cart'],
                ['razorpay', 'name'],
                ['razorpay', 'description', 'cart'],
                ['razorpay', 'metaData', 'cart'],
                ['razorpay', 'name', 'cart'],
                ['mollie', 'description', 'cart'],
                ['mollie', 'metaData', 'cart'],
                ['mollie', 'cancelBooking'],
                ['square', 'enabled'],
                ['square', 'description', 'cart'],
                ['square', 'metaData', 'cart'],
                ['square', 'locationId'],
                ['square', 'accessToken'],
                ['square', 'testMode'],
                ['square', 'clientLiveId'],
                ['square', 'clientTestId'],
                ['square', 'countryCode'],
            ],
            $settings
        );
    }

    /**
     * Init Purchase Code Settings
     */
    private static function initActivationSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings('activation');

        $isNewInstallation = empty($savedSettings);

        $settings = [
            'showActivationSettings'        => true,
            'active'                        => false,
            'purchaseCodeStore'             => '',
            'envatoTokenEmail'              => '',
            'version'                       => '',
            'deleteTables'                  => false,
            'showAmeliaPromoCustomizePopup' => true,
            'showAmeliaSurvey'              => true,
            'stash'                         => false,
            'responseErrorAsConflict'       => $savedSettings ? false : true,
            'hideUnavailableFeatures'       => true,
            'disableUrlParams'              => $savedSettings ? false : true,
            'enableThriveItems'             => false,
            'customUrl'                     => [
                'enabled'     => false,
                'pluginPath'  => '/wp-content/plugins/ameliabooking/',
                'ajaxPath'    => '/wp-admin/admin-ajax.php',
                'uploadsPath' => '',
            ],
            'v3RelativePath'                => false,
            'v3AsyncLoading'                => false,
            'premiumBannerVisibility'       => true,
            'dismissibleBannerVisibility'   => true,
        ];

        self::initSettings('activation', $settings);

        $savedSettings['showAmeliaPromoCustomizePopup'] = true;

        $savedSettings['isNewInstallation'] = $isNewInstallation;

        self::initSettings('activation', $savedSettings, true);
    }

    /**
     * Init Customization Settings
     *
     * @throws Exception
     */
    private static function initCustomizationSettings()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $settings = $settingsService->getCategorySettings('customization');
        unset($settings['hash']);

        $lessParserService = new LessParserService(
            AMELIA_PATH . '/assets/less/frontend/amelia-booking.less',
            AMELIA_UPLOADS_PATH . '/amelia/css',
            $settingsService
        );

        if (!$settings) {
            $settings = [
                'primaryColor'                => '#1A84EE',
                'primaryGradient1'            => '#1A84EE',
                'primaryGradient2'            => '#0454A2',
                'textColor'                   => '#354052',
                'textColorOnBackground'       => '#FFFFFF',
                'font'                        => 'Amelia Roboto',
                'fontUrl'                     => '',
                'customFontFamily'            => '',
                'customFontSelected'          => 'unselected',
                'useGenerated'                => false,
            ];
        }

        if (!isset($settings['fontUrl'])) {
            $settings = array_merge(
                $settings,
                [
                    'fontUrl' => ''
                ]
            );
        }

        if (!isset($settings['customFontFamily'])) {
            $settings = array_merge(
                $settings,
                [
                    'customFontFamily' => ''
                ]
            );
        }

        if (!isset($settings['customFontSelected'])) {
            $settings = array_merge(
                $settings,
                [
                    'customFontSelected' => 'unselected'
                ]
            );
        }

        if (!isset($settings['useGlobalColors'])) {
            $settings = array_merge(
                $settings,
                [
                    'useGlobalColors' => [
                        'stepByStepForm'    => false,
                        'catalogForm'       => false,
                        'eventListForm'     => false,
                        'eventCalendarForm' => false,
                    ]
                ]
            );
        }

        if (!isset($settings['globalColors'])) {
            $settings = array_merge(
                $settings,
                [
                    'globalColors' => [
                        'primaryColor'          => $settings['primaryColor'],
                        'formBackgroundColor'   => '#FFFFFF',
                        'formTextColor'         => $settings['textColor'],
                        'formInputColor'        => '#FFFFFF',
                        'formInputTextColor'    => $settings['textColor'],
                        'formDropdownColor'     => '#FFFFFF',
                        'formDropdownTextColor' => $settings['textColor'],
                        'formGradientColor1'    => $settings['primaryGradient1'],
                        'formGradientColor2'    => $settings['primaryGradient2'],
                        'formGradientAngle'     => 135,
                        'formImageColor'        => $settings['primaryColor'],
                        'textColorOnBackground' => $settings['textColorOnBackground'],
                    ]
                ]
            );
        }

        if (empty($settings['primaryColor'])) {
            $settings['primaryColor'] = 'rgba(255,255,255,0)';
            $settings['globalColors']['primaryColor']   = 'rgba(255,255,255,0)';
            $settings['globalColors']['formImageColor'] = 'rgba(255,255,255,0)';
        }

        if (empty($settings['primaryGradient1'])) {
            $settings['primaryGradient1'] = 'rgba(255,255,255,0)';
            $settings['globalColors']['formGradientColor1'] = 'rgba(255,255,255,0)';
        }

        if (empty($settings['primaryGradient2'])) {
            $settings['primaryGradient2'] = 'rgba(255,255,255,0)';
            $settings['globalColors']['formGradientColor2'] = 'rgba(255,255,255,0)';
        }

        if (empty($settings['textColor'])) {
            $settings['textColor'] = 'rgba(255,255,255,0)';
            $settings['globalColors']['formTextColor']         = 'rgba(255,255,255,0)';
            $settings['globalColors']['formInputTextColor']    = 'rgba(255,255,255,0)';
            $settings['globalColors']['formDropdownTextColor'] = 'rgba(255,255,255,0)';
        }

        if (empty($settings['textColorOnBackground'])) {
            $settings['textColorOnBackground'] = 'rgba(255,255,255,0)';
            $settings['globalColors']['textColorOnBackground'] = 'rgba(255,255,255,0)';
        }

        $globalColors = $settings['globalColors'];

        $settingsForm = [];

        if (isset($settings['forms']['stepByStepForm'])) {
            $useGlobalSbs = $settings['useGlobalColors']['stepByStepForm'];
            $colorSbsSsf  = $settings['forms']['stepByStepForm']['selectServiceForm']['globalSettings'];
            $colorSbsCf   = $settings['forms']['stepByStepForm']['calendarDateTimeForm']['globalSettings'];
            $colorSbsRsf  = $settings['forms']['stepByStepForm']['recurringSetupForm']['globalSettings'];
            $colorSbsRdf  = $settings['forms']['stepByStepForm']['recurringDatesForm']['globalSettings'];
            $colorSbsCaf  = $settings['forms']['stepByStepForm']['confirmBookingForm']['appointment']['globalSettings'];
            $colorSbsSpf  = $settings['forms']['stepByStepForm']['selectPackageForm']['globalSettings'];
            $colorSbsPif  = $settings['forms']['stepByStepForm']['packageInfoForm']['globalSettings'];
            $colorSbsPsf  = $settings['forms']['stepByStepForm']['packageSetupForm']['globalSettings'];
            $colorSbsPlf  = $settings['forms']['stepByStepForm']['packageListForm']['globalSettings'];
            $colorSbsCpf  = $settings['forms']['stepByStepForm']['confirmBookingForm']['package']['globalSettings'];
            $colorSbsCoa  = $settings['forms']['stepByStepForm']['congratulationsForm']['appointment']['globalSettings'];
            $colorSbsCop  = $settings['forms']['stepByStepForm']['congratulationsForm']['package']['globalSettings'];
            $settingsForm = array_merge(
                $settingsForm,
                [
                    'sbs-ssf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsSsf['formBackgroundColor'],
                    'sbs-ssf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsSsf['formTextColor'],
                    'sbs-ssf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsSsf['formInputColor'],
                    'sbs-ssf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsSsf['formInputTextColor'],
                    'sbs-ssf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsSsf['formDropdownColor'],
                    'sbs-ssf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsSsf['formDropdownTextColor'],
                    'sbs-cf-gradient1'            => $useGlobalSbs ? $globalColors['formGradientColor1'] : $colorSbsCf['formGradientColor1'],
                    'sbs-cf-gradient2'            => $useGlobalSbs ? $globalColors['formGradientColor2'] : $colorSbsCf['formGradientColor2'],
                    'sbs-cf-gradient-angle'       => $useGlobalSbs ? $globalColors['formGradientAngle'] . 'deg' : $colorSbsCf['formGradientAngle'] . 'deg',
                    'sbs-cf-text-color'           => $useGlobalSbs ? $globalColors['textColorOnBackground'] : $colorSbsCf['formTextColor'],
                    'sbs-rsf-gradient1'           => $useGlobalSbs ? $globalColors['formGradientColor1'] : $colorSbsRsf['formGradientColor1'],
                    'sbs-rsf-gradient2'           => $useGlobalSbs ? $globalColors['formGradientColor2'] : $colorSbsRsf['formGradientColor2'],
                    'sbs-rsf-gradient-angle'      => $useGlobalSbs ? $globalColors['formGradientAngle'] . 'deg' : $colorSbsRsf['formGradientAngle'] . 'deg',
                    'sbs-rsf-text-color'          => $useGlobalSbs ? $globalColors['textColorOnBackground'] : $colorSbsRsf['formTextColor'],
                    'sbs-rsf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsRsf['formInputColor'],
                    'sbs-rsf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsRsf['formInputTextColor'],
                    'sbs-rsf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsRsf['formDropdownColor'],
                    'sbs-rsf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsRsf['formDropdownTextColor'],
                    'sbs-rdf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsRdf['formBackgroundColor'],
                    'sbs-rdf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsRdf['formTextColor'],
                    'sbs-rdf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsRdf['formInputColor'],
                    'sbs-rdf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsRdf['formInputTextColor'],
                    'sbs-rdf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsRdf['formDropdownColor'],
                    'sbs-rdf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsRdf['formDropdownTextColor'],
                    'sbs-caf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCaf['formBackgroundColor'],
                    'sbs-caf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCaf['formTextColor'],
                    'sbs-caf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCaf['formInputColor'],
                    'sbs-caf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCaf['formInputTextColor'],
                    'sbs-caf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCaf['formDropdownColor'],
                    'sbs-caf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCaf['formDropdownTextColor'],
                    'sbs-spf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsSpf['formBackgroundColor'],
                    'sbs-spf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsSpf['formTextColor'],
                    'sbs-spf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsSpf['formInputColor'],
                    'sbs-spf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsSpf['formInputTextColor'],
                    'sbs-spf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsSpf['formDropdownColor'],
                    'sbs-spf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsSpf['formDropdownTextColor'],
                    'sbs-pif-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsPif['formBackgroundColor'],
                    'sbs-pif-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsPif['formTextColor'],
                    'sbs-psf-gradient1'           => $useGlobalSbs ? $globalColors['formGradientColor1'] : $colorSbsPsf['formGradientColor1'],
                    'sbs-psf-gradient2'           => $useGlobalSbs ? $globalColors['formGradientColor2'] : $colorSbsPsf['formGradientColor2'],
                    'sbs-psf-gradient-angle'      => $useGlobalSbs ? $globalColors['formGradientAngle'] . 'deg' : $colorSbsPsf['formGradientAngle'] . 'deg',
                    'sbs-psf-text-color'          => $useGlobalSbs ? $globalColors['textColorOnBackground'] : $colorSbsPsf['formTextColor'],
                    'sbs-psf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsPsf['formInputColor'],
                    'sbs-psf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsPsf['formInputTextColor'],
                    'sbs-psf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsPsf['formDropdownColor'],
                    'sbs-psf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsPsf['formDropdownTextColor'],
                    'sbs-plf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsPlf['formBackgroundColor'],
                    'sbs-plf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsPlf['formTextColor'],
                    'sbs-cpf-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCpf['formBackgroundColor'],
                    'sbs-cpf-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCpf['formTextColor'],
                    'sbs-cpf-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCpf['formInputColor'],
                    'sbs-cpf-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCpf['formInputTextColor'],
                    'sbs-cpf-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCpf['formDropdownColor'],
                    'sbs-cpf-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCpf['formDropdownTextColor'],
                    'sbs-coa-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCoa['formBackgroundColor'],
                    'sbs-coa-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCoa['formTextColor'],
                    'sbs-coa-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCoa['formInputColor'],
                    'sbs-coa-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCoa['formInputTextColor'],
                    'sbs-coa-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCoa['formDropdownColor'],
                    'sbs-coa-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCoa['formDropdownTextColor'],
                    'sbs-cop-bgr-color'           => $useGlobalSbs ? $globalColors['formBackgroundColor'] : $colorSbsCop['formBackgroundColor'],
                    'sbs-cop-text-color'          => $useGlobalSbs ? $globalColors['formTextColor'] : $colorSbsCop['formTextColor'],
                    'sbs-cop-input-color'         => $useGlobalSbs ? $globalColors['formInputColor'] : $colorSbsCop['formInputColor'],
                    'sbs-cop-input-text-color'    => $useGlobalSbs ? $globalColors['formInputTextColor'] : $colorSbsCop['formInputTextColor'],
                    'sbs-cop-dropdown-color'      => $useGlobalSbs ? $globalColors['formDropdownColor'] : $colorSbsCop['formDropdownColor'],
                    'sbs-cop-dropdown-text-color' => $useGlobalSbs ? $globalColors['formDropdownTextColor'] : $colorSbsCop['formDropdownTextColor'],
                ]
            );
        } else {
            $settingsForm = array_merge(
                $settingsForm,
                [
                    'sbs-ssf-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-ssf-text-color'          => $globalColors['formTextColor'],
                    'sbs-ssf-input-color'         => $globalColors['formInputColor'],
                    'sbs-ssf-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-ssf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-ssf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-cf-gradient1'            => $globalColors['formGradientColor1'],
                    'sbs-cf-gradient2'            => $globalColors['formGradientColor2'],
                    'sbs-cf-gradient-angle'       => $globalColors['formGradientAngle'] . 'deg',
                    'sbs-cf-text-color'           => $globalColors['textColorOnBackground'],
                    'sbs-rsf-gradient1'           => $globalColors['formGradientColor1'],
                    'sbs-rsf-gradient2'           => $globalColors['formGradientColor2'],
                    'sbs-rsf-gradient-angle'      => $globalColors['formGradientAngle'] . 'deg',
                    'sbs-rsf-text-color'          => $globalColors['textColorOnBackground'],
                    'sbs-rsf-input-color'         => $globalColors['formInputColor'],
                    'sbs-rsf-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-rsf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-rsf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-rdf-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-rdf-text-color'          => $globalColors['formTextColor'],
                    'sbs-rdf-input-color'         => $globalColors['formInputColor'],
                    'sbs-rdf-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-rdf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-rdf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-caf-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-caf-text-color'          => $globalColors['formTextColor'],
                    'sbs-caf-input-color'         => $globalColors['formInputColor'],
                    'sbs-caf-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-caf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-caf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-spf-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-spf-text-color'          => $globalColors['formTextColor'],
                    'sbs-spf-input-color'         => $globalColors['formInputColor'],
                    'sbs-spf-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-spf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-spf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-pif-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-pif-text-color'          => $globalColors['formTextColor'],
                    'sbs-psf-gradient1'           => $globalColors['formGradientColor1'],
                    'sbs-psf-gradient2'           => $globalColors['formGradientColor2'],
                    'sbs-psf-gradient-angle'      => $globalColors['formGradientAngle'] . 'deg',
                    'sbs-psf-text-color'          => $globalColors['textColorOnBackground'],
                    'sbs-psf-input-color'         => $globalColors['formInputColor'],
                    'sbs-psf-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-psf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-psf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-plf-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-plf-text-color'          => $globalColors['formTextColor'],
                    'sbs-cpf-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-cpf-text-color'          => $globalColors['formTextColor'],
                    'sbs-cpf-input-color'         => $globalColors['formInputColor'],
                    'sbs-cpf-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-cpf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-cpf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-coa-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-coa-text-color'          => $globalColors['formTextColor'],
                    'sbs-coa-input-color'         => $globalColors['formInputColor'],
                    'sbs-coa-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-coa-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-coa-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'sbs-cop-bgr-color'           => $globalColors['formBackgroundColor'],
                    'sbs-cop-text-color'          => $globalColors['formTextColor'],
                    'sbs-cop-input-color'         => $globalColors['formInputColor'],
                    'sbs-cop-input-text-color'    => $globalColors['formInputTextColor'],
                    'sbs-cop-dropdown-color'      => $globalColors['formDropdownColor'],
                    'sbs-cop-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                ]
            );
        }

        if (isset($settings['forms']['catalogForm'])) {
            $useGlobalCf  = $settings['useGlobalColors']['catalogForm'];
            $colorCfSsf   = $settings['forms']['catalogForm']['selectServiceForm']['globalSettings'];
            $colorCfCf    = $settings['forms']['catalogForm']['calendarDateTimeForm']['globalSettings'];
            $colorCfRsf   = $settings['forms']['catalogForm']['recurringSetupForm']['globalSettings'];
            $colorCfRdf   = $settings['forms']['catalogForm']['recurringDatesForm']['globalSettings'];
            $colorCfCaf   = $settings['forms']['catalogForm']['confirmBookingForm']['appointment']['globalSettings'];
            $colorCfPsf   = $settings['forms']['catalogForm']['packageSetupForm']['globalSettings'];
            $colorCfPlf   = $settings['forms']['catalogForm']['packageListForm']['globalSettings'];
            $colorCfCpf   = $settings['forms']['catalogForm']['confirmBookingForm']['package']['globalSettings'];
            $colorCfCoa   = $settings['forms']['catalogForm']['congratulationsForm']['appointment']['globalSettings'];
            $colorCfCop   = $settings['forms']['catalogForm']['congratulationsForm']['package']['globalSettings'];
            $settingsForm = array_merge(
                $settingsForm,
                [
                    'cf-ssf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfSsf['formBackgroundColor'],
                    'cf-ssf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfSsf['formTextColor'],
                    'cf-ssf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfSsf['formInputColor'],
                    'cf-ssf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfSsf['formInputTextColor'],
                    'cf-ssf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfSsf['formDropdownColor'],
                    'cf-ssf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfSsf['formDropdownTextColor'],
                    'cf-cf-gradient1'             => $useGlobalCf ? $globalColors['formGradientColor1'] : $colorCfCf['formGradientColor1'],
                    'cf-cf-gradient2'             => $useGlobalCf ? $globalColors['formGradientColor2'] : $colorCfCf['formGradientColor2'],
                    'cf-cf-gradient-angle'        => $useGlobalCf ? $globalColors['formGradientAngle'] . 'deg' : $colorCfCf['formGradientAngle'] . 'deg',
                    'cf-cf-text-color'            => $useGlobalCf ? $globalColors['textColorOnBackground'] : $colorCfCf['formTextColor'],
                    'cf-rsf-gradient1'            => $useGlobalCf ? $globalColors['formGradientColor1'] : $colorCfRsf['formGradientColor1'],
                    'cf-rsf-gradient2'            => $useGlobalCf ? $globalColors['formGradientColor2'] : $colorCfRsf['formGradientColor2'],
                    'cf-rsf-gradient-angle'       => $useGlobalCf ? $globalColors['formGradientAngle'] . 'deg' : $colorCfRsf['formGradientAngle'] . 'deg',
                    'cf-rsf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfRsf['formTextColor'],
                    'cf-rsf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfRsf['formInputColor'],
                    'cf-rsf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfRsf['formInputTextColor'],
                    'cf-rsf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfRsf['formDropdownColor'],
                    'cf-rsf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfRsf['formDropdownTextColor'],
                    'cf-rdf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfRdf['formBackgroundColor'],
                    'cf-rdf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfRdf['formTextColor'],
                    'cf-rdf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfRdf['formInputColor'],
                    'cf-rdf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfRdf['formInputTextColor'],
                    'cf-rdf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfRdf['formDropdownColor'],
                    'cf-rdf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfRdf['formDropdownTextColor'],
                    'cf-caf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCaf['formBackgroundColor'],
                    'cf-caf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCaf['formTextColor'],
                    'cf-caf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCaf['formInputColor'],
                    'cf-caf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCaf['formInputTextColor'],
                    'cf-caf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCaf['formDropdownColor'],
                    'cf-caf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCaf['formDropdownTextColor'],
                    'cf-psf-gradient1'            => $useGlobalCf ? $globalColors['formGradientColor1'] : $colorCfPsf['formGradientColor1'],
                    'cf-psf-gradient2'            => $useGlobalCf ? $globalColors['formGradientColor2'] : $colorCfPsf['formGradientColor2'],
                    'cf-psf-gradient-angle'       => $useGlobalCf ? $globalColors['formGradientAngle'] . 'deg' : $colorCfPsf['formGradientAngle'] . 'deg',
                    'cf-psf-text-color'           => $useGlobalCf ? $globalColors['textColorOnBackground'] : $colorCfPsf['formTextColor'],
                    'cf-psf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfPsf['formInputColor'],
                    'cf-psf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfPsf['formInputTextColor'],
                    'cf-psf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfPsf['formDropdownColor'],
                    'cf-psf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfPsf['formDropdownTextColor'],
                    'cf-plf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfPlf['formBackgroundColor'],
                    'cf-plf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfPlf['formTextColor'],
                    'cf-cpf-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCpf['formBackgroundColor'],
                    'cf-cpf-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCpf['formTextColor'],
                    'cf-cpf-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCpf['formInputColor'],
                    'cf-cpf-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCpf['formInputTextColor'],
                    'cf-cpf-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCpf['formDropdownColor'],
                    'cf-cpf-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCpf['formDropdownTextColor'],
                    'cf-coa-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCoa['formBackgroundColor'],
                    'cf-coa-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCoa['formTextColor'],
                    'cf-coa-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCoa['formInputColor'],
                    'cf-coa-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCoa['formInputTextColor'],
                    'cf-coa-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCoa['formDropdownColor'],
                    'cf-coa-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCoa['formDropdownTextColor'],
                    'cf-cop-bgr-color'            => $useGlobalCf ? $globalColors['formBackgroundColor'] : $colorCfCop['formBackgroundColor'],
                    'cf-cop-text-color'           => $useGlobalCf ? $globalColors['formTextColor'] : $colorCfCop['formTextColor'],
                    'cf-cop-input-color'          => $useGlobalCf ? $globalColors['formInputColor'] : $colorCfCop['formInputColor'],
                    'cf-cop-input-text-color'     => $useGlobalCf ? $globalColors['formInputTextColor'] : $colorCfCop['formInputTextColor'],
                    'cf-cop-dropdown-color'       => $useGlobalCf ? $globalColors['formDropdownColor'] : $colorCfCop['formDropdownColor'],
                    'cf-cop-dropdown-text-color'  => $useGlobalCf ? $globalColors['formDropdownTextColor'] : $colorCfCop['formDropdownTextColor'],
                ]
            );
        } else {
            $settingsForm = array_merge(
                $settingsForm,
                [
                    'cf-ssf-bgr-color'            => $globalColors['formBackgroundColor'],
                    'cf-ssf-text-color'           => $globalColors['formTextColor'],
                    'cf-ssf-input-color'          => $globalColors['formInputColor'],
                    'cf-ssf-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-ssf-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-ssf-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                    'cf-cf-gradient1'             => $globalColors['formGradientColor1'],
                    'cf-cf-gradient2'             => $globalColors['formGradientColor2'],
                    'cf-cf-gradient-angle'        => $globalColors['formGradientAngle'] . 'deg',
                    'cf-cf-text-color'            => $globalColors['textColorOnBackground'],
                    'cf-rsf-gradient1'            => $globalColors['formGradientColor1'],
                    'cf-rsf-gradient2'            => $globalColors['formGradientColor2'],
                    'cf-rsf-gradient-angle'       => $globalColors['formGradientAngle'] . 'deg',
                    'cf-rsf-text-color'           => $globalColors['textColorOnBackground'],
                    'cf-rsf-input-color'          => $globalColors['formInputColor'],
                    'cf-rsf-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-rsf-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-rsf-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                    'cf-rdf-bgr-color'            => $globalColors['formBackgroundColor'],
                    'cf-rdf-text-color'           => $globalColors['formTextColor'],
                    'cf-rdf-input-color'          => $globalColors['formInputColor'],
                    'cf-rdf-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-rdf-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-rdf-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                    'cf-caf-bgr-color'            => $globalColors['formBackgroundColor'],
                    'cf-caf-text-color'           => $globalColors['formTextColor'],
                    'cf-caf-input-color'          => $globalColors['formInputColor'],
                    'cf-caf-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-caf-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-caf-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                    'cf-psf-gradient1'            => $globalColors['formGradientColor1'],
                    'cf-psf-gradient2'            => $globalColors['formGradientColor2'],
                    'cf-psf-gradient-angle'       => $globalColors['formGradientAngle'] . 'deg',
                    'cf-psf-text-color'           => $globalColors['textColorOnBackground'],
                    'cf-psf-input-color'          => $globalColors['formInputColor'],
                    'cf-psf-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-psf-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-psf-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                    'cf-plf-bgr-color'            => $globalColors['formBackgroundColor'],
                    'cf-plf-text-color'           => $globalColors['formTextColor'],
                    'cf-cpf-bgr-color'            => $globalColors['formBackgroundColor'],
                    'cf-cpf-text-color'           => $globalColors['formTextColor'],
                    'cf-cpf-input-color'          => $globalColors['formInputColor'],
                    'cf-cpf-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-cpf-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-cpf-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                    'cf-coa-bgr-color'            => $globalColors['formBackgroundColor'],
                    'cf-coa-text-color'           => $globalColors['formTextColor'],
                    'cf-coa-input-color'          => $globalColors['formInputColor'],
                    'cf-coa-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-coa-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-coa-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                    'cf-cop-bgr-color'            => $globalColors['formBackgroundColor'],
                    'cf-cop-text-color'           => $globalColors['formTextColor'],
                    'cf-cop-input-color'          => $globalColors['formInputColor'],
                    'cf-cop-input-text-color'     => $globalColors['formInputTextColor'],
                    'cf-cop-dropdown-color'       => $globalColors['formDropdownColor'],
                    'cf-cop-dropdown-text-color'  => $globalColors['formDropdownTextColor'],
                ]
            );
        }

        if (isset($settings['forms']['eventListForm'])) {
            $useGlobaElf = $settings['useGlobalColors']['eventListForm'];
            $colorElf    = $settings['forms']['eventListForm']['globalSettings'];

            $settingsForm = array_merge(
                $settingsForm,
                [
                    'elf-bgr-color'           => $useGlobaElf ? $globalColors['formBackgroundColor'] : $colorElf['formBackgroundColor'],
                    'elf-text-color'          => $useGlobaElf ? $globalColors['formTextColor'] : $colorElf['formTextColor'],
                    'elf-input-color'         => $useGlobaElf ? $globalColors['formInputColor'] : $colorElf['formInputColor'],
                    'elf-input-text-color'    => $useGlobaElf ? $globalColors['formInputTextColor'] : $colorElf['formInputTextColor'],
                    'elf-dropdown-color'      => $useGlobaElf ? $globalColors['formDropdownColor'] : $colorElf['formDropdownColor'],
                    'elf-dropdown-text-color' => $useGlobaElf ? $globalColors['formDropdownTextColor'] : $colorElf['formDropdownTextColor'],
                ]
            );
        } else {
            $settingsForm = array_merge(
                $settingsForm,
                [
                    'elf-bgr-color'           => $globalColors['formBackgroundColor'],
                    'elf-text-color'          => $globalColors['formTextColor'],
                    'elf-input-color'         => $globalColors['formInputColor'],
                    'elf-input-text-color'    => $globalColors['formInputTextColor'],
                    'elf-dropdown-color'      => $globalColors['formDropdownColor'],
                    'elf-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                ]
            );
        }

        if (isset($settings['forms']['eventCalendarForm'])) {
            $useGlobalEcf = $settings['useGlobalColors']['eventCalendarForm'];
            $colorEcfCef  = $settings['forms']['eventCalendarForm']['confirmBookingForm']['event']['globalSettings'];
            $colorEcfCoe  = $settings['forms']['eventCalendarForm']['congratulationsForm']['event']['globalSettings'];
            $settingsForm = array_merge(
                $settingsForm,
                [
                    'ecf-cef-bgr-color'           => $useGlobalEcf ? $globalColors['formBackgroundColor'] : $colorEcfCef['formBackgroundColor'],
                    'ecf-cef-text-color'          => $useGlobalEcf ? $globalColors['formTextColor'] : $colorEcfCef['formTextColor'],
                    'ecf-cef-input-color'         => $useGlobalEcf ? $globalColors['formInputColor'] : $colorEcfCef['formInputColor'],
                    'ecf-cef-input-text-color'    => $useGlobalEcf ? $globalColors['formInputTextColor'] : $colorEcfCef['formInputTextColor'],
                    'ecf-cef-dropdown-color'      => $useGlobalEcf ? $globalColors['formDropdownColor'] : $colorEcfCef['formDropdownColor'],
                    'ecf-cef-dropdown-text-color' => $useGlobalEcf ? $globalColors['formDropdownTextColor'] : $colorEcfCef['formDropdownTextColor'],
                    'ecf-coe-bgr-color'           => $useGlobalEcf ? $globalColors['formBackgroundColor'] : $colorEcfCoe['formBackgroundColor'],
                    'ecf-coe-text-color'          => $useGlobalEcf ? $globalColors['formTextColor'] : $colorEcfCoe['formTextColor'],
                    'ecf-coe-input-color'         => $useGlobalEcf ? $globalColors['formInputColor'] : $colorEcfCoe['formInputColor'],
                    'ecf-coe-input-text-color'    => $useGlobalEcf ? $globalColors['formInputTextColor'] : $colorEcfCoe['formInputTextColor'],
                    'ecf-coe-dropdown-color'      => $useGlobalEcf ? $globalColors['formDropdownColor'] : $colorEcfCoe['formDropdownColor'],
                    'ecf-coe-dropdown-text-color' => $useGlobalEcf ? $globalColors['formDropdownTextColor'] : $colorEcfCoe['formDropdownTextColor'],
                ]
            );
        } else {
            $settingsForm = array_merge(
                $settingsForm,
                [
                    'ecf-cef-bgr-color'           => $globalColors['formBackgroundColor'],
                    'ecf-cef-text-color'          => $globalColors['formTextColor'],
                    'ecf-cef-input-color'         => $globalColors['formInputColor'],
                    'ecf-cef-input-text-color'    => $globalColors['formInputTextColor'],
                    'ecf-cef-dropdown-color'      => $globalColors['formDropdownColor'],
                    'ecf-cef-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                    'ecf-coe-bgr-color'           => $globalColors['formBackgroundColor'],
                    'ecf-coe-text-color'          => $globalColors['formTextColor'],
                    'ecf-coe-input-color'         => $globalColors['formInputColor'],
                    'ecf-coe-input-text-color'    => $globalColors['formInputTextColor'],
                    'ecf-coe-dropdown-color'      => $globalColors['formDropdownColor'],
                    'ecf-coe-dropdown-text-color' => $globalColors['formDropdownTextColor'],
                ]
            );
        }

        if ($settingsService->getSetting('customization', 'useGenerated')) {
            $hash = $lessParserService->compileAndSave(
                array_merge(
                    [
                        'color-accent'         => $globalColors['primaryColor'],
                        'color-white'          => $globalColors['textColorOnBackground'],
                        'color-text-prime'     => $globalColors['formTextColor'],
                        'color-text-second'    => $globalColors['formTextColor'],
                        'color-bgr'            => $globalColors['formBackgroundColor'],
                        'color-gradient1'      => $globalColors['formGradientColor1'],
                        'color-gradient2'      => $globalColors['formGradientColor2'],
                        'color-dropdown'       => $globalColors['formDropdownColor'],
                        'color-dropdown-text'  => $globalColors['formDropdownTextColor'],
                        'color-input'          => $globalColors['formInputColor'],
                        'color-input-text'     => $globalColors['formInputTextColor'],
                        'font'                 => !empty($settings['font']) ? $settings['font'] : '',
                        'custom-font-selected' => $settings['customFontSelected'],
                        'font-url'             => !empty($settings['fontUrl']) ? $settings['fontUrl'] : '',
                    ],
                    $settingsForm
                )
            );

            $settings['hash'] = $hash;
        }

        $settingsService->fixCustomization($settings);

        self::initSettings('customization', $settings, true);
    }

    /**
     * Init Labels Settings
     */
    private static function initLabelsSettings()
    {
        $settings = [
            'enabled'   => true,
            'employee'  => 'employee',
            'employees' => 'employees',
            'service'   => 'service',
            'services'  => 'services'
        ];

        self::initSettings('labels', $settings);
    }

    /**
     * Init Roles Settings
     *
     * @return array
     */
    public static function getDefaultRolesSettings()
    {
        return [
            'allowConfigureSchedule'      => false,
            'allowConfigureDaysOff'       => false,
            'allowConfigureSpecialDays'   => false,
            'allowConfigureServices'      => false,
            'allowWriteAppointments'      => false,
            'allowWriteCustomers'         => false,
            'automaticallyCreateCustomer' => true,
            'inspectCustomerInfo'         => false,
            'allowCustomerReschedule'     => false,
            'allowCustomerCancelPackages' => true,
            'allowCustomerDeleteProfile'  => false,
            'allowWriteEvents'            => false,
            'allowAdminBookAtAnyTime'     => false,
            'allowAdminBookOverApp'       => false,
            'adminServiceDurationAsSlot'  => false,
            'enabledHttpAuthorization'    => true,
            'enableNoShowTag'             => true,
            'customerCabinet'             => [
                'enabled'         => true,
                'headerJwtSecret' => (new Token(null, 20))->getValue(),
                'urlJwtSecret'    => (new Token(null, 20))->getValue(),
                'tokenValidTime'  => 2592000,
                'pageUrl'         => '',
                'loginEnabled'    => true,
                'filterDate'      => false,
                'translations'    => [],
                'googleRecaptcha' => false,
            ],
            'providerCabinet'             => [
                'enabled'         => true,
                'headerJwtSecret' => (new Token(null, 20))->getValue(),
                'urlJwtSecret'    => (new Token(null, 20))->getValue(),
                'tokenValidTime'  => 2592000,
                'pageUrl'         => '',
                'loginEnabled'    => true,
                'filterDate'      => false,
                'googleRecaptcha' => false,
            ],
            'urlAttachment'       => [
                'enabled'         => true,
                'headerJwtSecret' => (new Token(null, 20))->getValue(),
                'urlJwtSecret'    => (new Token(null, 20))->getValue(),
                'tokenValidTime'  => 2592000,
                'pageUrl'         => '',
                'loginEnabled'    => true,
                'filterDate'      => false,
            ],
            'limitPerCustomerService' => [
                'enabled'     => false,
                'numberOfApp' => 1,
                'timeFrame'   => 'day',
                'period'      => 1,
                'from'        => 'bookingDate'
            ],
            'limitPerCustomerPackage' => [
                'enabled'     => false,
                'numberOfApp' => 1,
                'timeFrame'   => 'day',
                'period'      => 1,
            ],
            'limitPerCustomerEvent' => [
                'enabled'     => false,
                'numberOfApp' => 1,
                'timeFrame'   => 'day',
                'period'      => 1,
                'from'        => 'bookingDate'
            ],
            'limitPerEmployee' => [
                'enabled'     => false,
                'numberOfApp' => 1,
                'timeFrame'   => 'day',
                'period'      => 1,
            ],
            'providerBadges'  => [
                'counter' => 3,
                'badges'  => [
                    [
                        'id'      => 1,
                        'content' => 'Most Popular',
                        'color'   => '#1246D6'
                    ],
                    [
                        'id'      => 2,
                        'content' => 'Top Performer',
                        'color'   => '#019719'
                    ],
                    [
                        'id'      => 3,
                        'content' => 'Exclusive',
                        'color'   => '#CCA20C'
                    ],
                ]
            ],
        ];
    }

    /**
     * Init Roles Settings
     */
    private static function initRolesSettings()
    {
        $settings = self::getDefaultRolesSettings();

        self::initSettings('roles', $settings);

        self::setNewSettingsToExistingSettings(
            'roles',
            [
                ['customerCabinet', 'filterDate'],
                ['customerCabinet', 'translations'],
                ['customerCabinet', 'headerJwtSecret'],
                ['customerCabinet', 'urlJwtSecret'],
                ['customerCabinet', 'googleRecaptcha'],
                ['providerCabinet', 'headerJwtSecret'],
                ['providerCabinet', 'urlJwtSecret'],
                ['providerCabinet', 'googleRecaptcha'],
                ['urlAttachment', 'headerJwtSecret'],
                ['urlAttachment', 'urlJwtSecret'],
            ],
            $settings
        );
    }

    /**
     * Get Appointments Settings
     *
     * @return array
     */
    public static function getDefaultAppointmentsSettings()
    {
        return [
            'isGloballyBusySlot'                => false,
            'bookMultipleTimes'                 => false,
            'allowBookingIfPending'             => true,
            'allowBookingIfNotMin'              => true,
            'openedBookingAfterMin'             => false,
            'cartPlaceholders'                  => '<!-- Content --><p>DateTime: %appointment_date_time%</p>',
            'cartPlaceholdersSms'               => 'DateTime: %appointment_date_time%',
            'cartPlaceholdersCustomer'          => '<!-- Content --><p>DateTime: %appointment_date_time%</p>',
            'cartPlaceholdersCustomerSms'       => 'DateTime: %appointment_date_time%',
            'recurringPlaceholders'             => 'DateTime: %appointment_date_time%',
            'recurringPlaceholdersSms'          => 'DateTime: %appointment_date_time%',
            'recurringPlaceholdersCustomer'     => 'DateTime: %appointment_date_time%',
            'recurringPlaceholdersCustomerSms'  => 'DateTime: %appointment_date_time%',
            'packagePlaceholders'               => 'DateTime: %appointment_date_time%',
            'packagePlaceholdersSms'            => 'DateTime: %appointment_date_time%',
            'packagePlaceholdersCustomer'       => 'DateTime: %appointment_date_time%',
            'packagePlaceholdersCustomerSms'    => 'DateTime: %appointment_date_time%',
            'groupAppointmentPlaceholder'       => 'Name: %customer_full_name%',
            'groupEventPlaceholder'             => 'Name: %customer_full_name%',
            'groupAppointmentPlaceholderSms'    => 'Name: %customer_full_name%',
            'groupEventPlaceholderSms'          => 'Name: %customer_full_name%',
            'translations'                      => [
                'cartPlaceholdersCustomer'         => null,
                'cartPlaceholdersCustomerSms'      => null,
                'recurringPlaceholdersCustomer'    => null,
                'recurringPlaceholdersCustomerSms' => null,
                'packagePlaceholdersCustomer'      => null,
                'packagePlaceholdersCustomerSms'   => null,
                'groupAppointmentPlaceholder'      => 'Name: %customer_full_name%',
                'groupEventPlaceholder'            => 'Name: %customer_full_name%',
                'groupAppointmentPlaceholderSms'   => 'Name: %customer_full_name%',
                'groupEventPlaceholderSms'         => 'Name: %customer_full_name%',
            ],
            'waitingListEvents'                 => [
                'enabled'                          => false,
                'addingMethod'                     => 'Manually'
            ],
            'pastDaysEvents'                       => 0,
            'employeeSelection'                    => 'random',
            'bringingAnyoneLogic'                  => 'additional',
        ];
    }

    /**
     * Init Appointments Settings
     */
    private static function initAppointmentsSettings()
    {
        $settings = self::getDefaultAppointmentsSettings();

        self::initSettings('appointments', $settings);

        self::setNewSettingsToExistingSettings(
            'appointments',
            [
                ['translations', 'cartPlaceholdersCustomer'],
                ['translations', 'cartPlaceholdersCustomerSms'],
            ],
            $settings
        );
    }

    /**
     * Init Web Hooks Settings
     */
    private static function initWebHooksSettings()
    {
        $settings = [];

        self::initSettings('webHooks', $settings);
    }

    /**
     * get Back Link Setting
     */
    private static function getBackLinkSetting()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $backLinksLabels = [
            'Generated with Amelia - WordPress Booking Plugin',
            'Powered by Amelia - WordPress Booking Plugin',
            'Booking by Amelia  - WordPress Booking Plugin',
            'Powered by Amelia - Appointment and Events Booking Plugin',
            'Powered by Amelia - Appointment and Event Booking Plugin',
            'Powered by Amelia - WordPress Booking Plugin',
            'Generated with Amelia - Appointment and Event Booking Plugin',
            'Booking Enabled by Amelia - Appointment and Event Booking Plugin',
        ];

        $backLinksUrls = [
            'https://wpamelia.com/?utm_source=lite&utm_medium=websites&utm_campaign=powerdby',
            'https://wpamelia.com/demos/?utm_source=lite&utm_medium=website&utm_campaign=powerdby#Features-list',
            'https://wpamelia.com/pricing/?utm_source=lite&utm_medium=website&utm_campaign=powerdby',
            'https://wpamelia.com/documentation/?utm_source=lite&utm_medium=website&utm_campaign=powerdby',
        ];

        return [
            'enabled' => $settingsService->getCategorySettings('general') === null,
            'label'   => $backLinksLabels[rand(0, 7)],
            'url'     => $backLinksUrls[rand(0, 3)],
        ];
    }

    /**
     * Add new settings ti global parent settings
     *
     * @param string $category
     * @param array  $pathsKeys
     * @param array  $initSettings
     */
    private static function setNewSettingsToExistingSettings($category, $pathsKeys, $initSettings)
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $savedSettings = $settingsService->getCategorySettings($category);

        $setSettings = false;

        foreach ($pathsKeys as $keys) {
            $current = &$savedSettings;

            $currentInit = &$initSettings;

            foreach ((array)$keys as $key) {
                if (!isset($current[$key])) {
                    $current[$key] = !empty($currentInit[$key]) ? $currentInit[$key] : null;

                    $setSettings = true;

                    continue 2;
                }

                $current = &$current[$key];

                $currentInit = &$currentInit[$key];
            }
        }

        if ($setSettings) {
            self::initSettings($category, $savedSettings, true);
        }
    }

    /**
     * Init Social Login Settings
     */
    private static function initSocialLoginSettings()
    {
        $settings = [
            'enableGoogleLogin'   => false,
            'enableFacebookLogin' => false,
            'facebookAppId'       => '',
            'facebookAppSecret'   => '',
        ];

        self::initSettings('socialLogin', $settings);
    }
}
