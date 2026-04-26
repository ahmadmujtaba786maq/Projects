<?php

namespace AmeliaBooking\Domain\Services\Settings;

/**
 * Interface SettingsStorageInterface
 *
 * @package AmeliaBooking\Domain\Services\Settings
 */
interface SettingsStorageInterface
{
    /**
     * @param $settingCategoryKey
     * @param $settingKey
     *
     * @return mixed
     */
    public function getSetting($settingCategoryKey, $settingKey);

    /**
     * @param $settingCategoryKey
     *
     * @return mixed
     */
    public function getCategorySettings($settingCategoryKey);

    /**
     * @return mixed
     */
    public function getAllSettings();

    /**
     * @return mixed
     */
    public function getAllSettingsCategorized();

    /**
     * @return mixed
     */
    public function getFrontendSettings();

    /**
     * @param $settingCategoryKey
     * @param $settingKey
     * @param $settingValue
     *
     * @return mixed
     */
    public function setSetting($settingCategoryKey, $settingKey, $settingValue);

    /**
     * @param $settingCategoryKey
     * @param $settingValue
     *
     * @return mixed
     */
    public function setCategorySettings($settingCategoryKey, $settingValue);

    /**
     * @param $settings
     *
     * @return mixed
     */
    public function setAllSettings($settings);
}
