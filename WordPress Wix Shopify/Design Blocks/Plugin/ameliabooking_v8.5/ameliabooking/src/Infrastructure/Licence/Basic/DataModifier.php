<?php

namespace AmeliaBooking\Infrastructure\Licence\Basic;

use AmeliaBooking\Domain\Services\DateTime\DateTimeService;

/**
 * Class DataModifier
 *
 * @package AmeliaBooking\Infrastructure\Licence\Basic
 */
class DataModifier extends \AmeliaBooking\Infrastructure\Licence\Starter\DataModifier
{
    /**
     * @param array $settings
     * @param array $savedSettings
     */
    public static function restoreSettings(&$settings, $savedSettings)
    {
    }

    /**
     * @param array $settings
     * @param array $savedSettings
     */
    public static function commonRestoreSettings(&$settings, $savedSettings)
    {
    }

    /**
     * @param array $settings
     */
    public static function modifySettings(&$settings)
    {
        if ($settings && isset($settings['payments'])) {
            $settings['payments']['cart'] = false;
        }

        if ($settings && isset($settings['payments']['stripe']['connect'])) {
            $settings['payments']['stripe']['connect']['enabled'] = false;
        }

        if ($settings && isset($settings['notifications'])) {
            $settings['notifications']['whatsAppEnabled'] = false;
        }
    }

    /**
     * @param array $settings
     */
    public static function commonModifySettings(&$settings)
    {
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getUserRepositoryData($data)
    {
        $starterData = parent::getUserRepositoryData($data);

        return [
            'values'                 =>
                array_merge(
                    $starterData['values'],
                    [
                    ':zoomUserId'       => isset($data['zoomUserId']) ? $data['zoomUserId'] : null,
                    ':appleCalendarId'  => isset($data['appleCalendarId']) ? $data['appleCalendarId'] : null,
                    ':translations'     => isset($data['translations']) ? $data['translations'] : null,
                    ':timeZone'         => isset($data['timeZone']) ? $data['timeZone'] : null,
                    ':badgeId'          => isset($data['badgeId']) ? $data['badgeId'] : null,
                    ]
                ),
            'columns'                =>
                $starterData['columns'] .
                '`zoomUserId`,
                `appleCalendarId`,
                `translations`,
                `timeZone`,
                `badgeId`,',
            'placeholders'           =>
                $starterData['placeholders'] .
                ':zoomUserId,
                :appleCalendarId,
                :translations,
                :timeZone,
                :badgeId,',
            'columnsPlaceholders'    =>
                $starterData['columnsPlaceholders'] .
                '`zoomUserId` = :zoomUserId,
                `appleCalendarId` = :appleCalendarId,
                `translations` = :translations,
                `timeZone` = :timeZone,
                `badgeId` = :badgeId,',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function userFactory(&$data)
    {
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getProviderServiceRepositoryData($data)
    {
        return [
            'values'                 =>
                [
                    ':customPricing' => $data['customPricing'],
                ],
            'columns'                =>
                '`customPricing`,',
            'placeholders'           =>
                ':customPricing,',
            'columnsPlaceholders'    =>
                '`customPricing` = :customPricing,',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function providerServiceFactory(&$data)
    {
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getPeriodRepositoryData($data)
    {
        return [
            'values'                 =>
                [
                    ':locationId' => $data['locationId'] ?: null,
                ],
            'columns'                =>
                '`locationId`,',
            'placeholders'           =>
                ':locationId,',
            'columnsPlaceholders'    =>
                '`locationId` = :locationId,',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function periodFactory(&$data)
    {
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getServiceRepositoryData($data)
    {
        $starterData = parent::getServiceRepositoryData($data);

        return [
            'values'                 =>
                array_merge(
                    $starterData['values'],
                    [
                        ':recurringCycle'   => $data['recurringCycle'],
                        ':recurringSub'     => $data['recurringSub'],
                        ':recurringPayment' => $data['recurringPayment'],
                        ':translations'     => $data['translations'],
                        ':deposit'          => $data['deposit'],
                        ':depositPayment'   => $data['depositPayment'],
                        ':depositPerPerson' => $data['depositPerPerson'] ? 1 : 0,
                        ':fullPayment'      => $data['fullPayment'] ? 1 : 0,
                        ':customPricing'    => $data['customPricing'],
                        ':limitPerCustomer' => $data['limitPerCustomer'],
                    ]
                ),
            'columns'                =>
                $starterData['columns'] .
                '`recurringCycle`,
                `recurringSub`,
                `recurringPayment`,
                `translations`,
                `deposit`,
                `depositPayment`,
                `depositPerPerson`,
                `fullPayment`,
                `customPricing`,
                `limitPerCustomer`,',
            'placeholders'           =>
                $starterData['placeholders'] .
                ':recurringCycle,
                :recurringSub,
                :recurringPayment,
                :translations,
                :deposit,
                :depositPayment,
                :depositPerPerson,
                :fullPayment,
                :customPricing,
                :limitPerCustomer,',
            'columnsPlaceholders'    =>
                $starterData['columnsPlaceholders'] .
                '`recurringCycle`    = :recurringCycle,
                `recurringSub`      = :recurringSub,
                `recurringPayment`  = :recurringPayment,
                `translations`      = :translations,
                `deposit`           = :deposit,
                `depositPayment`    = :depositPayment,
                `depositPerPerson`  = :depositPerPerson,
                `fullPayment`       = :fullPayment,
                `customPricing`     = :customPricing,
                `limitPerCustomer`  = :limitPerCustomer,',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function serviceFactory(&$data)
    {
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getEventRepositoryData($data)
    {
        $starterData = parent::getEventRepositoryData($data);

        return [
            'values'       =>  array_merge(
                $starterData['values'],
                [
                ':recurringCycle'       => $data['recurring'] && $data['recurring']['cycle'] ?
                    $data['recurring']['cycle'] : null,
                ':recurringOrder'       => $data['recurring'] && $data['recurring']['order'] ?
                    $data['recurring']['order'] : null,
                ':recurringInterval'    => $data['recurring'] && $data['recurring']['cycleInterval'] ?
                    $data['recurring']['cycleInterval'] : null,
                ':monthlyDate'          => $data['recurring'] && $data['recurring']['monthDate'] ?
                    DateTimeService::getCustomDateTimeInUtc($data['recurring']['monthDate']) : null,
                ':recurringUntil'       => $data['recurring'] && $data['recurring']['until'] ?
                    DateTimeService::getCustomDateTimeInUtc($data['recurring']['until']) : null,
                ':locationId'           => $data['locationId'],
                ':zoomUserId'           => $data['zoomUserId'],
                ':organizerId'          => $data['organizerId'],
                ':translations'         => $data['translations'],
                ':deposit'              => $data['deposit'],
                ':depositPayment'       => $data['depositPayment'],
                ':depositPerPerson'     => $data['depositPerPerson'] ? 1 : 0,
                ':fullPayment'          => $data['fullPayment'] ? 1 : 0,
                ':customPricing'        => $data['customPricing'] ? 1 : 0,
                ]
            ),
            'addValues'              => [
                ':recurringMonthly'     => $data['recurring'] && $data['recurring']['monthlyRepeat'] ?
                    $data['recurring']['monthlyRepeat'] : null,
                ':monthlyOnRepeat'      => $data['recurring'] && $data['recurring']['monthlyOnRepeat'] ?
                    $data['recurring']['monthlyOnRepeat'] : null,
                ':monthlyOnDay'         => $data['recurring'] && $data['recurring']['monthlyOnDay'] ?
                    $data['recurring']['monthlyOnDay'] : null,
            ],
            'columns'                =>
                $starterData['columns'] .
                '`recurringCycle`,
                `recurringOrder`,
                `recurringInterval`,
                `recurringMonthly`,
                `monthlyDate`,
                `monthlyOnRepeat`,
                `monthlyOnDay`,
                `recurringUntil`,
                `locationId`,
                `zoomUserId`,
                `organizerId`,
                `translations`,
                `deposit`,
                `depositPayment`,
                `depositPerPerson`,
                `fullPayment`,
                `customPricing`,',
            'placeholders'           =>
                $starterData['placeholders'] .
                ':recurringCycle,
                :recurringOrder,
                :recurringInterval,
                :recurringMonthly,
                :monthlyDate,
                :monthlyOnRepeat,
                :monthlyOnDay,           
                :recurringUntil,
                :locationId,
                :zoomUserId,
                :organizerId,
                :translations,
                :deposit,
                :depositPayment,
                :depositPerPerson,
                :fullPayment,
                :customPricing,',
            'columnsPlaceholders'    =>
                $starterData['columnsPlaceholders'] .
                '`recurringCycle` = :recurringCycle,
                `recurringOrder` = :recurringOrder,
                `recurringInterval` = :recurringInterval,
                `monthlyDate` = :monthlyDate,    
                `recurringUntil` = :recurringUntil,
                `locationId` = :locationId,
                `zoomUserId` = :zoomUserId,
                `organizerId` = :organizerId,
                `translations` = :translations,
                `deposit` = :deposit,
                `depositPayment` = :depositPayment,
                `depositPerPerson` = :depositPerPerson,
                `fullPayment` = :fullPayment,
                `customPricing` = :customPricing,',
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function eventFactory(&$data)
    {
    }
}
