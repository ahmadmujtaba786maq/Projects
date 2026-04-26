<?php

namespace AmeliaBooking\Application\Services\Helper;

use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\LoginType;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaFirebase\JWT\JWT;
use Interop\Container\Exception\ContainerException;
use DateTime;
use Exception;

/**
 * Class HelperService
 *
 * @package AmeliaBooking\Application\Services\Helper
 */
class HelperService
{
    private $container;

    /**
     * HelperService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Returns formatted price based on price plugin settings
     *
     * @param int|float $price
     *
     * @return string
     * @throws ContainerException
     */
    public function getFormattedPrice($price)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $paymentSettings = $settingsService->getCategorySettings('payments');

        // Price Separators
        $thousandSeparatorMap = [',', '.', ' ', ' '];

        $decimalSeparatorMap = ['.', ',', '.', ','];

        $thousandSeparator = $thousandSeparatorMap[$paymentSettings['priceSeparator'] - 1];

        $decimalSeparator = $decimalSeparatorMap[$paymentSettings['priceSeparator'] - 1];

        // Price Prefix
        $pricePrefix = '';
        if ($paymentSettings['priceSymbolPosition'] === 'before') {
            $pricePrefix = $paymentSettings['symbol'];
        } elseif ($paymentSettings['priceSymbolPosition'] === 'beforeWithSpace') {
            $pricePrefix = $paymentSettings['symbol'] . ' ';
        }

        // Price Suffix
        $priceSuffix = '';
        if ($paymentSettings['priceSymbolPosition'] === 'after') {
            $priceSuffix = $paymentSettings['symbol'];
        } elseif ($paymentSettings['priceSymbolPosition'] === 'afterWithSpace') {
            $priceSuffix = ' ' . $paymentSettings['symbol'];
        }

        $formattedNumber = number_format(
            $price,
            $paymentSettings['priceNumberOfDecimals'],
            $decimalSeparator,
            $thousandSeparator
        );

        return $pricePrefix . $formattedNumber . $priceSuffix;
    }

    /**
     * @param int $seconds
     *
     * @return string
     */
    public function secondsToNiceDuration($seconds)
    {
        $hours = floor($seconds / 3600);

        $minutes = $seconds / 60 % 60;

        return ($hours ? ($hours . 'h ') : '') . ($hours && $minutes ? ' ' : '') . ($minutes ? ($minutes . 'min') : '');
    }

    /**
     * @param string $email
     * @param string $secret
     * @param int    $expireTimeStamp
     * @param int    $loginType
     *
     * @return mixed
     * @throws Exception
     */
    public function getGeneratedJWT($email, $secret, $expireTimeStamp, $loginType)
    {
        $now = new DateTime();

        $data = [
            'iss'   => AMELIA_SITE_URL,
            'iat'   => $now->getTimestamp(),
            'email' => $email,
            'wp'    => $loginType
        ];

        if ($expireTimeStamp !== null) {
            $data['exp'] = $expireTimeStamp;
        }

        return JWT::encode($data, $secret);
    }

    /**
     * @param string $email
     * @param string $type
     * @param string $dateStartString
     * @param string $dateEndString
     * @param string $locale
     *
     * @return string
     *
     * @throws Exception
     */
    public function getCustomerCabinetUrl($email, $type, $dateStartString, $dateEndString, $locale, $changePass = false)
    {
        /** @var SettingsService $cabinetSettings */
        $cabinetSettings = $this->container->get('domain.settings.service')->getSetting('roles', 'customerCabinet');

        $cabinetPlaceholder = '';

        $usedLanguages = $this->container->get('domain.settings.service')->getSetting('general', 'usedLanguages');

        $cabinetURL = trim(
            $locale &&
            $usedLanguages &&
            in_array($locale, $usedLanguages) &&
            !empty($cabinetSettings['translations']['url'][$locale]) ?
            $cabinetSettings['translations']['url'][$locale] : $cabinetSettings['pageUrl']
        );

        if ($cabinetURL) {
            $tokenParam = $type === 'email' ? (strpos($cabinetURL, '?') === false ? '?token=' : '&token=') .
                $this->getGeneratedJWT(
                    $email,
                    $cabinetSettings['urlJwtSecret'],
                    DateTimeService::getNowDateTimeObject()->getTimestamp() + $cabinetSettings['tokenValidTime'],
                    LoginType::AMELIA_URL_TOKEN
                ) : '';

            $cabinetPlaceholder = substr($cabinetURL, -1) === '/' ?
                substr($cabinetURL, 0, -1) . $tokenParam : $cabinetURL . $tokenParam;

            if ($cabinetSettings['filterDate'] && $dateStartString && $dateEndString) {
                $cabinetPlaceholder .= '&end=' . $dateEndString . '&start=' . $dateStartString;
            }

            if ($changePass) {
                $cabinetPlaceholder .= '&changePass=' . $changePass;
            }

            $cabinetPlaceholder = apply_filters('amelia_customer_panel_placeholder_filter', $cabinetPlaceholder);
        }

        return $cabinetPlaceholder;
    }

    /**
     * @param string $email
     * @param string $type
     * @param string $dateStartString
     * @param string $dateEndString
     * @param bool $changePass
     *
     * @return string
     *
     * @throws Exception
     */
    public function getProviderCabinetUrl($email, $type, $dateStartString, $dateEndString, $changePass = false)
    {
        /** @var SettingsService $cabinetSettings */
        $cabinetSettings = $this->container->get('domain.settings.service')->getSetting('roles', 'providerCabinet');

        $cabinetPlaceholder = '';

        $cabinetURL = trim($cabinetSettings['pageUrl']);

        if ($cabinetURL) {
            $tokenParam = $type === 'email' ? (strpos($cabinetURL, '?') === false ? '?token=' : '&token=') .
                $this->getGeneratedJWT(
                    $email,
                    $cabinetSettings['urlJwtSecret'],
                    DateTimeService::getNowDateTimeObject()->getTimestamp() + $cabinetSettings['tokenValidTime'],
                    LoginType::AMELIA_URL_TOKEN
                ) : '';

            $cabinetPlaceholder = substr($cabinetURL, -1) === '/' ?
                substr($cabinetURL, 0, -1) . $tokenParam : $cabinetURL . $tokenParam;

            if ($cabinetSettings['filterDate'] && $dateStartString && $dateEndString) {
                $cabinetPlaceholder .= '&end=' . $dateEndString . '&start=' . $dateStartString;
            }

            if ($changePass) {
                $cabinetPlaceholder .= '&changePass=' . $changePass;
            }

            $cabinetPlaceholder = apply_filters('amelia_employee_panel_placeholder_filter', $cabinetPlaceholder);
        }

        return $cabinetPlaceholder;
    }

    /**
     * @param string $locale
     * @param string $entityTranslation
     * @param string $type
     *
     * @return array
     */
    public function getBookingTranslation($locale, $entityTranslation, $type)
    {
        $entityTranslation = !empty($entityTranslation) ? json_decode($entityTranslation, true) : null;

        if ($locale) {
            if ($type === null) {
                return
                    $entityTranslation &&
                    !empty($entityTranslation[$locale]) ?
                        $entityTranslation[$locale] : null;
            } else {
                return
                    $entityTranslation &&
                    !empty($entityTranslation[$type][$locale]) ?
                        $entityTranslation[$type][$locale] : null;
            }
        }

        return null;
    }

    /**
     * @param string $bookingInfo
     * @return string
     */
    public function getLocaleFromBooking($bookingInfo)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $usedLanguages = $settingsService->getSetting('general', 'usedLanguages');

        $bookingInfo = !empty($bookingInfo) ? json_decode($bookingInfo, true) : null;

        return $bookingInfo && !empty($bookingInfo['locale']) ? $this->getLocaleLanguage($usedLanguages, $bookingInfo['locale'])[0] : null;
    }

    /**
     * @param string $translations
     *
     * @return string
     */
    public function getLocaleFromTranslations($translations)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $usedLanguages = $settingsService->getSetting('general', 'usedLanguages');

        $translations = !empty($translations) ? json_decode($translations, true) : null;

        return $translations && !empty($translations['defaultLanguage'])
            ? $this->getLocaleLanguage($usedLanguages, $translations['defaultLanguage'])[0] : null;
    }

    /**
     * @param array  $usedLanguages
     * @param string $locale
     * @return array
     */
    public function getLocaleLanguage($usedLanguages, $locale)
    {
        $finalLanguages = [];
        foreach ($usedLanguages as $language) {
            if (explode('_', $language)[0] === explode('_', $locale)[0]) {
                $finalLanguages[] = $language;
            }
        }

        return empty($finalLanguages) ? [$locale] : $finalLanguages;
    }


    /**
     * @return array
     */
    public static function getLanguages()
    {
        return array(
            'af' => array(
                'name' => 'Afrikaans',
                'code' => 'af',
                'wp_locale' => 'af',
                'country_code' => 'za'
            ) ,
            'ar' => array(
                'name' => 'Arabic',
                'code' => 'ar',
                'wp_locale' => 'ar',
                'country_code' => 'sa'
            ) ,
            'ary' => array(
                'name' => 'Moroccan Arabic',
                'code' => 'ary',
                'wp_locale' => 'ary',
                'country_code' => 'ma'
            ) ,
            'as' => array(
                'name' => 'Assamese',
                'code' => 'as',
                'wp_locale' => 'as',
                'country_code' => 'in'
            )  ,
            'azb' => array(
                'name' => 'South Azerbaijani',
                'code' => 'azb',
                'wp_locale' => 'azb',
                'country_code' => 'az'
            ) ,
            'az' => array(
                'name' => 'Azerbaijani',
                'code' => 'az',
                'wp_locale' => 'az',
                'country_code' => 'az'
            ) ,
            'bel' => array(
                'name' => 'Belarusian',
                'code' => 'bel',
                'wp_locale' => 'bel',
                'country_code' => 'by'
            ) ,
            'bg_BG' => array(
                'name' => 'Bulgarian',
                'code' => 'bg',
                'wp_locale' => 'bg_BG',
                'country_code' => 'bg'
            ) ,
            'bn_BD' => array(
                'name' => 'Bengali',
                'code' => 'bn',
                'wp_locale' => 'bn_BD',
                'country_code' => 'bd'
            ) ,
            'bo' => array(
                'name' => 'Tibetan',
                'code' => 'bo',
                'wp_locale' => 'bo',
                'country_code' => 'cn'
            ) ,
            'bs_BA' => array(
                'name' => 'Bosnian',
                'code' => 'bs',
                'wp_locale' => 'bs_BA',
                'country_code' => 'ba'
            ) ,
            'ca' => array(
                'name' => 'Catalan',
                'code' => 'ca',
                'wp_locale' => 'ca',
                'country_code' => 'es'
            ) ,
            'ceb' => array(
                'name' => 'Cebuano',
                'code' => 'ceb',
                'wp_locale' => 'ceb',
                'country_code' => 'ph'
            ) ,
            'cs_CZ' => array(
                'name' => 'Czech',
                'code' => 'cs',
                'wp_locale' => 'cs_CZ',
                'country_code' => 'cz'
            ) ,
            'cy' => array(
                'name' => 'Welsh',
                'code' => 'cy',
                'wp_locale' => 'cy',
                'country_code' => 'gb'
            ) ,
            'da_DK' => array(
                'name' => 'Danish',
                'code' => 'da',
                'wp_locale' => 'da_DK',
                'country_code' => 'dk'
            ) ,
            'de_DE_formal' => array(
                'name' => 'German (formal)',
                'code' => 'de',
                'wp_locale' => 'de_DE_formal',
                'country_code' => 'de'
            ) ,
            'de_AT' => array(
                'name' => 'German (Austria)',
                'code' => 'de',
                'wp_locale' => 'de_AT',
                'country_code' => 'at'
            ) ,
            'de_DE' => array(
                'name' => 'German',
                'code' => 'de',
                'wp_locale' => 'de_DE',
                'country_code' => 'de'
            ) ,
            'de_CH' => array(
                'name' => 'German (Switzerland)',
                'code' => 'de-ch',
                'wp_locale' => 'de_CH',
                'country_code' => 'ch'
            ) ,
            'de_CH_informal' => array(
                'name' => 'German (Switzerland) informal',
                'code' => 'de-ch',
                'wp_locale' => 'de_CH_informal',
                'country_code' => 'ch'
            ) ,
            'dsb' => array(
                'name' => 'Lower Sorbian',
                'code' => 'dsb',
                'wp_locale' => 'dsb',
                'country_code' => 'de'
            ) ,
            'dzo' => array(
                'name' => 'Dzongkha',
                'code' => 'dzo',
                'wp_locale' => 'dzo',
                'country_code' => 'bt'
            ) ,
            'el' => array(
                'name' => 'Greek',
                'code' => 'el',
                'wp_locale' => 'el',
                'country_code' => 'gr'
            ) ,
            'en_AU' => array(
                'name' => 'English (Australia)',
                'code' => 'en-au',
                'wp_locale' => 'en_AU',
                'country_code' => 'au'
            ) ,
            'en_CA' => array(
                'name' => 'English (Canada)',
                'code' => 'en-ca',
                'wp_locale' => 'en_CA',
                'country_code' => 'ca'
            ) ,
            'en_GB' => array(
                'name' => 'English (UK)',
                'code' => 'en-gb',
                'wp_locale' => 'en_GB',
                'country_code' => 'gb'
            ) ,
            'en_US' => array(
                'name' => 'English (US)',
                'code' => 'en',
                'wp_locale' => 'en_US',
                'country_code' => 'us'
            ) ,
            'en_NZ' => array(
                'name' => 'English (New Zealand)',
                'code' => 'en',
                'wp_locale' => 'en_NZ',
                'country_code' => 'nz'
            ) ,
            'en_ZA' => array(
                'name' => 'English (South Africa)',
                'code' => 'en',
                'wp_locale' => 'en_ZA',
                'country_code' => 'za'
            ) ,
            'es_PE' => array(
                'name' => 'Spanish (Peru)',
                'code' => 'es-pe',
                'wp_locale' => 'es_PE',
                'country_code' => 'pe'
            ) ,
            'es_CR' => array(
                'name' => 'Spanish (Costa Rica)',
                'code' => 'es-cr',
                'wp_locale' => 'es_CR',
                'country_code' => 'cr'
            ) ,
            'es_EC' => array(
                'name' => 'Spanish (Ecuador)',
                'code' => 'es-ec',
                'wp_locale' => 'es_EC',
                'country_code' => 'ec'
            ) ,
            'es_CO' => array(
                'name' => 'Spanish (Colombia)',
                'code' => 'es-co',
                'wp_locale' => 'es_CO',
                'country_code' => 'co'
            ) ,
            'es_UY' => array(
                'name' => 'Spanish (Uruguay)',
                'code' => 'es-uy',
                'wp_locale' => 'es_UY',
                'country_code' => 'uy'
            ) ,
            'es_PR' => array(
                'name' => 'Spanish (Puerto Rico)',
                'code' => 'es-pr',
                'wp_locale' => 'es_PR',
                'country_code' => 'pr'
            ) ,
            'es_GT' => array(
                'name' => 'Spanish (Guatemala)',
                'code' => 'es',
                'wp_locale' => 'es_GT',
                'country_code' => 'gt'
            ) ,
            'es_AR' => array(
                'name' => 'Spanish (Argentina)',
                'code' => 'es-ar',
                'wp_locale' => 'es_AR',
                'country_code' => 'ar'
            ) ,
            'es_CL' => array(
                'name' => 'Spanish (Chile)',
                'code' => 'es-cl',
                'wp_locale' => 'es_CL',
                'country_code' => 'cl'
            ) ,
            'es_MX' => array(
                'name' => 'Spanish (Mexico)',
                'code' => 'es-mx',
                'wp_locale' => 'es_MX',
                'country_code' => 'mx'
            ) ,
            'es_ES' => array(
                'name' => 'Spanish (Spain)',
                'code' => 'es',
                'wp_locale' => 'es_ES',
                'country_code' => 'es'
            ) ,
            'es_VE' => array(
                'name' => 'Spanish (Venezuela)',
                'code' => 'es-ve',
                'wp_locale' => 'es_VE',
                'country_code' => 've'
            ) ,
            'et' => array(
                'name' => 'Estonian',
                'code' => 'et',
                'wp_locale' => 'et',
                'country_code' => 'ee'
            ) ,
            'eu' => array(
                'name' => 'Basque',
                'code' => 'eu',
                'wp_locale' => 'eu',
                'country_code' => 'es'
            ) ,
            'fa_IR' => array(
                'name' => 'Persian',
                'code' => 'fa',
                'wp_locale' => 'fa_IR',
                'country_code' => 'ir'
            ) ,
            'fa_AF' => array(
                'name' => 'Persian (Afghanistan)',
                'code' => 'fa-af',
                'wp_locale' => 'fa_AF',
                'country_code' => 'af'
            ) ,
            'fi' => array(
                'name' => 'Finnish',
                'code' => 'fi',
                'wp_locale' => 'fi',
                'country_code' => 'fi'
            ) ,
            'fr_FR' => array(
                'name' => 'French (France)',
                'code' => 'fr',
                'wp_locale' => 'fr_FR',
                'country_code' => 'fr'
            ) ,
            'fr_CA' => array(
                'name' => 'French (Canada)',
                'code' => 'fr',
                'wp_locale' => 'fr_CA',
                'country_code' => 'ca'
            ) ,
            'fr_BE' => array(
                'name' => 'French (Belgium)',
                'code' => 'fr-be',
                'wp_locale' => 'fr_BE',
                'country_code' => 'be'
            ) ,
            'fur' => array(
                'name' => 'Friulian',
                'code' => 'fur',
                'wp_locale' => 'fur',
                'country_code' => 'it'
            ) ,
            'gd' => array(
                'name' => 'Scottish Gaelic',
                'code' => 'gd',
                'wp_locale' => 'gd',
                'country_code' => 'gb'
            ) ,
            'gl_ES' => array(
                'name' => 'Galician',
                'code' => 'gl',
                'wp_locale' => 'gl_ES',
                'country_code' => 'es'
            ) ,
            'gu' => array(
                'name' => 'Gujarati',
                'code' => 'gu',
                'wp_locale' => 'gu',
                'country_code' => 'in'
            ) ,
            'haz' => array(
                'name' => 'Hazaragi',
                'code' => 'haz',
                'wp_locale' => 'haz',
                'country_code' => 'af'
            ) ,
            'he_IL' => array(
                'name' => 'Hebrew',
                'code' => 'he',
                'wp_locale' => 'he_IL',
                'country_code' => 'il'
            ) ,
            'hi_IN' => array(
                'name' => 'Hindi',
                'code' => 'hi',
                'wp_locale' => 'hi_IN',
                'country_code' => 'in'
            ) ,
            'hr' => array(
                'name' => 'Croatian',
                'code' => 'hr',
                'wp_locale' => 'hr',
                'country_code' => 'hr'
            ) ,
            'hsb' => array(
                'name' => 'Upper Sorbian',
                'code' => 'hsb',
                'wp_locale' => 'hsb',
                'country_code' => 'de'
            ) ,
            'hu_HU' => array(
                'name' => 'Hungarian',
                'code' => 'hu',
                'wp_locale' => 'hu_HU',
                'country_code' => 'hu'
            ) ,
            'hy' => array(
                'name' => 'Armenian',
                'code' => 'hy',
                'wp_locale' => 'hy',
                'country_code' => 'am'
            ) ,
            'id_ID' => array(
                'name' => 'Indonesian',
                'code' => 'id',
                'wp_locale' => 'id_ID',
                'country_code' => 'id'
            ) ,
            'is_IS' => array(
                'name' => 'Icelandic',
                'code' => 'is',
                'wp_locale' => 'is_IS',
                'country_code' => 'is'
            ) ,
            'it_IT' => array(
                'name' => 'Italian',
                'code' => 'it',
                'wp_locale' => 'it_IT',
                'country_code' => 'it'
            ) ,
            'ja' => array(
                'name' => 'Japanese',
                'code' => 'ja',
                'wp_locale' => 'ja',
                'country_code' => 'jp'
            ) ,
            'jv_ID' => array(
                'name' => 'Javanese',
                'code' => 'jv',
                'wp_locale' => 'jv_ID',
                'country_code' => 'id'
            ) ,
            'ka_GE' => array(
                'name' => 'Georgian',
                'code' => 'ka',
                'wp_locale' => 'ka_GE',
                'country_code' => 'ge'
            ) ,
            'kab' => array(
                'name' => 'Kabyle',
                'code' => 'kab',
                'wp_locale' => 'kab',
                'country_code' => 'dz'
            ) ,
            'kk' => array(
                'name' => 'Kazakh',
                'code' => 'kk',
                'wp_locale' => 'kk',
                'country_code' => 'kz'
            ) ,
            'km' => array(
                'name' => 'Khmer',
                'code' => 'km',
                'wp_locale' => 'km',
                'country_code' => 'kh'
            ) ,
            'kn' => array(
                'name' => 'Kannada',
                'code' => 'kn',
                'wp_locale' => 'kn',
                'country_code' => 'in'
            ) ,
            'ko_KR' => array(
                'name' => 'Korean',
                'code' => 'ko',
                'wp_locale' => 'ko_KR',
                'country_code' => 'kr'
            ) ,
            'lo' => array(
                'name' => 'Lao',
                'code' => 'lo',
                'wp_locale' => 'lo',
                'country_code' => 'la'
            ) ,
            'lt_LT' => array(
                'name' => 'Lithuanian',
                'code' => 'lt',
                'wp_locale' => 'lt_LT',
                'country_code' => 'lt'
            ) ,
            'lb_LU' => array(
                'name' => 'Luxembourgish',
                'code' => 'lb',
                'wp_locale' => 'lb_LU',
                'country_code' => 'lu'
            ) ,
            'lv' => array(
                'name' => 'Latvian',
                'code' => 'lv',
                'wp_locale' => 'lv',
                'country_code' => 'lv'
            ) ,
            'mk_MK' => array(
                'name' => 'Macedonian',
                'code' => 'mk',
                'wp_locale' => 'mk_MK',
                'country_code' => 'mk'
            ) ,
            'ml_IN' => array(
                'name' => 'Malayalam',
                'code' => 'ml',
                'wp_locale' => 'ml_IN',
                'country_code' => 'in'
            ) ,
            'mn' => array(
                'name' => 'Mongolian',
                'code' => 'mn',
                'wp_locale' => 'mn',
                'country_code' => 'mn'
            ) ,
            'mr' => array(
                'name' => 'Marathi',
                'code' => 'mr',
                'wp_locale' => 'mr',
                'country_code' => 'in'
            ) ,
            'ms_MY' => array(
                'name' => 'Malay',
                'code' => 'ms',
                'wp_locale' => 'ms_MY',
                'country_code' => 'my'
            ) ,
            'my_MM' => array(
                'name' => 'Burmese',
                'code' => 'mya',
                'wp_locale' => 'my_MM',
                'country_code' => 'mm'
            ) ,
            'nb_NO' => array(
                'name' => 'Norwegian (Bokmål)',
                'code' => 'nb',
                'wp_locale' => 'nb_NO',
                'country_code' => 'no'
            ) ,
            'ne_NP' => array(
                'name' => 'Nepali',
                'code' => 'ne',
                'wp_locale' => 'ne_NP',
                'country_code' => 'np'
            ) ,
            'nl_BE' => array(
                'name' => 'Dutch (Belgium)',
                'code' => 'nl-be',
                'wp_locale' => 'nl_BE',
                'country_code' => 'be'
            ) ,
            'nl_NL' => array(
                'name' => 'Dutch',
                'code' => 'nl',
                'wp_locale' => 'nl_NL',
                'country_code' => 'nl'
            ) ,
            'nl_NL_formal' => array(
                'name' => 'Dutch (formal)',
                'code' => 'nl',
                'wp_locale' => 'nl_NL_formal',
                'country_code' => 'nl'
            ) ,
            'nn_NO' => array(
                'name' => 'Norwegian (Nynorsk)',
                'code' => 'nn',
                'wp_locale' => 'nn_NO',
                'country_code' => 'no'
            ) ,
            'pa_IN' => array(
                'name' => 'Punjabi',
                'code' => 'pa',
                'wp_locale' => 'pa_IN',
                'country_code' => 'in'
            ) ,
            'pl_PL' => array(
                'name' => 'Polish',
                'code' => 'pl',
                'wp_locale' => 'pl_PL',
                'country_code' => 'pl'
            ) ,
            'ps' => array(
                'name' => 'Pashto',
                'code' => 'ps',
                'wp_locale' => 'ps',
                'country_code' => 'af'
            ) ,
            'pt_PT_ao90' => array(
                'name' => 'Portuguese (AO90)',
                'code' => 'pt',
                'wp_locale' => 'pt_PT_ao90',
                'country_code' => 'pt'
            ) ,
            'pt_PT' => array(
                'name' => 'Portuguese (Portugal)',
                'code' => 'pt',
                'wp_locale' => 'pt_PT',
                'country_code' => 'pt'
            ) ,
            'pt_AO' => array(
                'name' => 'Portuguese (Angola)',
                'code' => 'pt-ao',
                'wp_locale' => 'pt_AO',
                'country_code' => 'ao'
            ) ,
            'pt_BR' => array(
                'name' => 'Portuguese (Brazil)',
                'code' => 'pt-br',
                'wp_locale' => 'pt_BR',
                'country_code' => 'br'
            ) ,
            'rhg' => array(
                'name' => 'Rohingya',
                'code' => 'rhg',
                'wp_locale' => 'rhg',
                'country_code' => 'mm'
            ) ,
            'ro_RO' => array(
                'name' => 'Romanian',
                'code' => 'ro',
                'wp_locale' => 'ro_RO',
                'country_code' => 'ro'
            ) ,
            'ru_RU' => array(
                'name' => 'Russian',
                'code' => 'ru',
                'wp_locale' => 'ru_RU',
                'country_code' => 'ru'
            ) ,
            'sah' => array(
                'name' => 'Sakha',
                'code' => 'sah',
                'wp_locale' => 'sah',
                'country_code' => 'ru'
            ) ,
            'snd' => array(
                'name' => 'Sindhi',
                'code' => 'snd',
                'wp_locale' => 'snd',
                'country_code' => 'pk'
            ) ,
            'si_LK' => array(
                'name' => 'Sinhala',
                'code' => 'si',
                'wp_locale' => 'si_LK',
                'country_code' => 'lk'
            ) ,
            'sk_SK' => array(
                'name' => 'Slovak',
                'code' => 'sk',
                'wp_locale' => 'sk_SK',
                'country_code' => 'sk'
            ) ,
            'skr' => array(
                'name' => 'Saraiki',
                'code' => 'skr',
                'wp_locale' => 'skr',
                'country_code' => 'pk'
            ) ,
            'sl_SI' => array(
                'name' => 'Slovenian',
                'code' => 'sl',
                'wp_locale' => 'sl_SI',
                'country_code' => 'si'
            ) ,
            'sq' => array(
                'name' => 'Albanian',
                'code' => 'sq',
                'wp_locale' => 'sq',
                'country_code' => 'al'
            ) ,
            'sr_RS' => array(
                'name' => 'Serbian',
                'code' => 'sr',
                'wp_locale' => 'sr_RS',
                'country_code' => 'rs'
            ) ,
            'sv_SE' => array(
                'name' => 'Swedish',
                'code' => 'sv',
                'wp_locale' => 'sv_SE',
                'country_code' => 'se'
            ) ,
            'sw' => array(
                'name' => 'Kiswahili',
                'code' => 'sw',
                'wp_locale' => 'sw',
                'country_code' => 'ke'
            ) ,
            'szl' => array(
                'name' => 'Silesian',
                'code' => 'szl',
                'wp_locale' => 'szl',
                'country_code' => 'pl'
            ) ,
            'ta_IN' => array(
                'name' => 'Tamil',
                'code' => 'ta',
                'wp_locale' => 'ta_IN',
                'country_code' => 'in'
            ) ,
            'ta_LK' => array(
                'name' => 'Tamil (Sri Lanka)',
                'code' => 'ta-lk',
                'wp_locale' => 'ta_LK',
                'country_code' => 'lk'
            ) ,
            'te' => array(
                'name' => 'Telugu',
                'code' => 'te',
                'wp_locale' => 'te',
                'country_code' => 'in'
            ) ,
            'th' => array(
                'name' => 'Thai',
                'code' => 'th',
                'wp_locale' => 'th',
                'country_code' => 'th'
            ) ,
            'tl' => array(
                'name' => 'Tagalog',
                'code' => 'tl',
                'wp_locale' => 'tl',
                'country_code' => 'ph'
            ) ,
            'tr_TR' => array(
                'name' => 'Turkish',
                'code' => 'tr',
                'wp_locale' => 'tr_TR',
                'country_code' => 'tr'
            ) ,
            'tah' => array(
                'name' => 'Tahitian',
                'code' => 'tah',
                'wp_locale' => 'tah',
                'country_code' => 'pf'
            ) ,
            'ug_CN' => array(
                'name' => 'Uighur',
                'code' => 'ug',
                'wp_locale' => 'ug_CN',
                'country_code' => 'cn'
            ) ,
            'uk' => array(
                'name' => 'Ukrainian',
                'code' => 'uk',
                'wp_locale' => 'uk',
                'country_code' => 'ua'
            ) ,
            'ur' => array(
                'name' => 'Urdu',
                'code' => 'ur',
                'wp_locale' => 'ur',
                'country_code' => 'pk'
            ) ,
            'uz_UZ' => array(
                'name' => 'Uzbek',
                'code' => 'uz',
                'wp_locale' => 'uz_UZ',
                'country_code' => 'uz'
            ) ,
            'vi' => array(
                'name' => 'Vietnamese',
                'code' => 'vi',
                'wp_locale' => 'vi',
                'country_code' => 'vn'
            ) ,
            'zh_HK' => array(
                'name' => 'Chinese (Hong Kong)',
                'code' => 'zh-hk',
                'wp_locale' => 'zh_HK',
                'country_code' => 'hk'
            ) ,
            'zh_CN' => array(
                'name' => 'Chinese (China)',
                'code' => 'zh-cn',
                'wp_locale' => 'zh_CN',
                'country_code' => 'cn'
            ) ,
            'zh_TW' => array(
                'name' => 'Chinese (Taiwan)',
                'code' => 'zh-tw',
                'wp_locale' => 'zh_TW',
                'country_code' => 'tw'
            )
        );
    }
}
