<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Services\DateTime;

/**
 * Class DateTimeService
 *
 * @package AmeliaBooking\Domain\Services\DateTime
 */
class DateTimeService
{
    /** @var \DateTimeZone */
    private static $timeZone;

    /**
     * @param array $settings
     */
    public static function setTimeZone($settings)
    {
        if (!self::$timeZone) {
            if ($settings['timeZoneString']) {
                self::$timeZone = new \DateTimeZone($settings['timeZoneString']);
            } elseif ($settings['gmtOffset']) {
                $hours   = (int)$settings['gmtOffset'];
                $minutes = ($settings['gmtOffset'] - floor($settings['gmtOffset'])) * 60;

                self::$timeZone = new \DateTimeZone(sprintf('%+03d:%02d', $hours, $minutes));
            } else {
                self::$timeZone = new \DateTimeZone('UTC');
            }
        }
    }

    /**
     * @param string $timeZone
     *
     * @return \DateTimeZone
     */
    public static function createTimeZone($timeZone)
    {
        return new \DateTimeZone($timeZone);
    }

    /**
     * @return \DateTimeZone
     */
    public static function getTimeZone()
    {
        return self::$timeZone;
    }

    /**
     * Return now date and time object by timezone settings
     *
     * @return \DateTime
     */
    public static function getNowDateTimeObject()
    {
        return new \DateTime('now', self::getTimeZone());
    }

    /**
     * Return now date and time string by timezone settings
     *
     * @return string
     */
    public static function getNowDateTime()
    {
        return self::getNowDateTimeObject()->format('Y-m-d H:i:s');
    }

    /**
     * @param String $dateTimeString
     *
     * @return \DateTime
     */
    public static function getCustomDateTimeObject($dateTimeString)
    {
        return new \DateTime($dateTimeString ? $dateTimeString : 'now', self::getTimeZone());
    }

    /**
     * Return custom date and time string by timezone settings
     *
     * @param String $dateTimeString
     *
     * @return string
     */
    public static function getCustomDateTime($dateTimeString)
    {
        return self::getCustomDateTimeObject($dateTimeString)->format('Y-m-d H:i:s');
    }

    /**
     * @param String $dateTimeString
     * @param int    $clientUtcOffset
     *
     * @return \DateTime
     */
    public static function getClientUtcCustomDateTimeObject($dateTimeString, $clientUtcOffset)
    {
        $clientDateTime = new \DateTime($dateTimeString, new \DateTimeZone('UTC'));

        if ($clientUtcOffset > 0) {
            $clientDateTime->modify("+{$clientUtcOffset} minutes");
        } elseif ($clientUtcOffset < 0) {
            $clientDateTime->modify("{$clientUtcOffset} minutes");
        }

        return $clientDateTime;
    }

    /**
     * Return custom date and time string by utc offset
     *
     * @param String $dateTimeString
     * @param int    $clientUtcOffset
     *
     * @return string
     */
    public static function getClientUtcCustomDateTime($dateTimeString, $clientUtcOffset)
    {
        return self::getClientUtcCustomDateTimeObject($dateTimeString, $clientUtcOffset)->format('Y-m-d H:i:s');
    }

    /**
     * Return now date and time object in UTC
     *
     * @return \DateTime
     */
    public static function getNowDateTimeObjectInUtc()
    {
        return self::getNowDateTimeObject()->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Return now date and time string in UTC
     *
     * @return string
     */
    public static function getNowDateTimeInUtc()
    {
        return self::getNowDateTimeObjectInUtc()->format('Y-m-d H:i:s');
    }

    /**
     * Return custom date and time object in UTC
     *
     * @param $dateTimeString
     *
     * @return \DateTime
     */
    public static function getCustomDateTimeObjectInUtc($dateTimeString)
    {
        return self::getCustomDateTimeObject($dateTimeString)->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Return custom date and time object in UTC
     *
     * @param $dateTimeString
     * @param $timezone
     *
     * @return \DateTime
     */
    public static function getCustomDateTimeObjectInTimeZone($dateTimeString, $timezone)
    {
        return self::getCustomDateTimeObject($dateTimeString)->setTimezone(new \DateTimeZone($timezone));
    }

    /**
     * Return custom date and time object in UTC
     *
     * @param $dateTimeString
     * @param $timezone
     *
     * @return \DateTime
     */
    public static function getDateTimeObjectInTimeZone($dateTimeString, $timezone)
    {
        return (new \DateTime($dateTimeString, new \DateTimeZone($timezone)));
    }

    /**
     * Return custom date and time string in UTC
     *
     * @param $dateTimeString
     *
     * @return string
     */
    public static function getCustomDateTimeInUtc($dateTimeString)
    {
        return self::getCustomDateTimeObjectInUtc($dateTimeString)->format('Y-m-d H:i:s');
    }

    /**
     * Return custom date and time object from UTC
     *
     * @param $dateTimeString
     *
     * @return \DateTime
     */
    public static function getCustomDateTimeObjectFromUtc($dateTimeString)
    {
        return (new \DateTime($dateTimeString, new \DateTimeZone('UTC')))->setTimezone(self::getTimeZone());
    }

    /**
     * Return custom date and time string from UTC
     *
     * @param $dateTimeString
     *
     * @return string
     */
    public static function getCustomDateTimeFromUtc($dateTimeString)
    {
        return self::getCustomDateTimeObjectFromUtc($dateTimeString)->format('Y-m-d H:i:s');
    }

    /**
     * Return custom date and time RFC3339 from UTC
     *
     * @param string $dateTimeString
     *
     * @return string
     */
    public static function getCustomDateTimeRFC3339($dateTimeString)
    {
        return self::getCustomDateTimeObjectInUtc($dateTimeString)->format(DATE_RFC3339);
    }

    /**
     * Return now date string by timezone settings
     *
     * @return string
     */
    public static function getNowDate()
    {
        return self::getNowDateTimeObject()->format('Y-m-d');
    }

    /**
     * Return now time string by timezone settings
     *
     * @return string
     */
    public static function getNowTime()
    {
        return self::getNowDateTimeObject()->format('H:i:s');
    }

    /**
     * Return current Unix timestamp
     *
     * @return false|int
     */
    public static function getNowTimestamp()
    {
        return strtotime(self::getNowTime());
    }

    /**
     * Return Day Index for passed date string in 'YYYY-MM-DD' format.
     * Monday index is 1, Sunday index is 7.
     *
     * @param $dateString
     *
     * @return int
     */
    public static function getDayIndex($dateString)
    {
        return self::getCustomDateTimeObject($dateString)->format('N');
    }

    /**
     * @param array $dateTimes
     *
     * @return array
     */
    public static function getSortedDateTimeStrings($dateTimes)
    {
        usort(
            $dateTimes,
            function ($a, $b) {
                return strtotime($a) - strtotime($b);
            }
        );

        return $dateTimes;
    }
}
