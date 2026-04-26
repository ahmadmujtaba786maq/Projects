<?php

namespace AmeliaBooking\Infrastructure\Licence\Starter;

/**
 * Class DataModifier
 *
 * @package AmeliaBooking\Infrastructure\Licence\Starter
 */
class DataModifier extends \AmeliaBooking\Infrastructure\Licence\Lite\DataModifier
{
    /**
     * @param array $settings
     * @param array $savedSettings
     */
    public static function restoreSettings(&$settings, $savedSettings)
    {
        self::commonRestoreSettings($settings, $savedSettings);
    }

    /**
     * @param array $settings
     * @param array $savedSettings
     */
    public static function commonRestoreSettings(&$settings, $savedSettings)
    {
        parent::commonRestoreSettings($settings, $savedSettings);
    }

    /**
     * @param array $settings
     */
    public static function modifySettings(&$settings)
    {
        self::commonModifySettings($settings);
    }

    /**
     * @param array $settings
     */
    public static function commonModifySettings(&$settings)
    {
        parent::commonModifySettings($settings);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function serviceFactory(&$data)
    {
        self::commonServiceFactory($data);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function commonServiceFactory(&$data)
    {
        parent::commonServiceFactory($data);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getUserRepositoryData($data)
    {
        return [
            'values'                 =>
                [
                    ':show'  => isset($data['show']) ? (int)$data['show'] : 1,
                ],
            'columns'             =>
                '`show`,',
            'placeholders'           =>
                ':show,',
            'columnsPlaceholders'    =>
                '`show` = :show,',
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getServiceRepositoryData($data)
    {
        return [
            'values'              =>
                [
                    ':settings'         => $data['settings'],
                    ':minCapacity'      => $data['minCapacity'],
                    ':maxCapacity'      => $data['maxCapacity'],
                    ':timeBefore'       => $data['timeBefore'],
                    ':timeAfter'        => $data['timeAfter'],
                    ':show'             => $data['show'] ? 1 : 0,
                ],
            'columns'             =>
                '`settings`,
                `timeBefore`,
                `timeAfter`,
                `show`,',
            'placeholders'        =>
                ':settings,
                :timeBefore,
                :timeAfter,
                :show,',
            'columnsPlaceholders' =>
                '`settings`          = :settings,
                `minCapacity`       = :minCapacity,
                `maxCapacity`       = :maxCapacity,
                `timeBefore`        = :timeBefore,
                `timeAfter`         = :timeAfter,
                `show`              = :show,',
        ];
    }


    /**
     * @param array $data
     *
     * @return array
     */
    public static function getEventRepositoryData($data)
    {
        return [
            'values'              =>
                [
                    ':settings'         => $data['settings']
                ],
            'addValues'           => [],
            'columns'             =>
                '`settings`,',
            'placeholders'        =>
                ':settings,',
            'columnsPlaceholders' =>
                '`settings`          = :settings,',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function eventFactory(&$data)
    {
        self::commonEventFactory($data);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function commonEventFactory(&$data)
    {
        parent::commonEventFactory($data);
    }
}
