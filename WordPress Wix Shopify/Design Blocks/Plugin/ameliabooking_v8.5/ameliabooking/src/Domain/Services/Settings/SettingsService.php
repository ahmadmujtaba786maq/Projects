<?php

namespace AmeliaBooking\Domain\Services\Settings;

use AmeliaBooking\Domain\Entity\Settings\Settings;
use AmeliaBooking\Domain\Factory\Settings\SettingsFactory;
use AmeliaBooking\Domain\ValueObjects\Json;

/**
 * Class SettingsService
 *
 * @package AmeliaBooking\Domain\Services\Settings
 */
class SettingsService
{
    public const NUMBER_OF_DAYS_AVAILABLE_FOR_BOOKING = 365;

    /** @var SettingsStorageInterface */
    private $settingsStorage;

    /**
     * SettingsService constructor.
     *
     * @param SettingsStorageInterface $settingsStorage
     */
    public function __construct(SettingsStorageInterface $settingsStorage)
    {
        $this->settingsStorage = $settingsStorage;
    }

    /**
     * @param      $settingCategoryKey
     * @param      $settingKey
     * @param null $defaultValue
     *
     * @return mixed|null
     */
    public function getSetting($settingCategoryKey, $settingKey, $defaultValue = null)
    {
        if (null !== $this->settingsStorage->getSetting($settingCategoryKey, $settingKey)) {
            return $this->settingsStorage->getSetting($settingCategoryKey, $settingKey);
        }

        return $defaultValue;
    }

    /**
     * @param $settingCategoryKey
     *
     * @return mixed|array
     */
    public function getCategorySettings($settingCategoryKey)
    {
        return $this->settingsStorage->getCategorySettings($settingCategoryKey);
    }

    /**
     * Return array of all settings where keys are settings names and values are settings values
     *
     * @return mixed
     */
    public function getAllSettings()
    {
        return $this->settingsStorage->getAllSettings();
    }

    /**
     * Return array of arrays where keys are settings categories names and values are categories settings
     *
     * @return mixed
     */
    public function getAllSettingsCategorized()
    {
        return $this->settingsStorage->getAllSettingsCategorized();
    }

    /**
     * @return mixed
     */
    public function getFrontendSettings()
    {
        return $this->settingsStorage->getFrontendSettings();
    }

    /**
     * @param $settingCategoryKey
     * @param $settingKey
     * @param $settingValue
     *
     * @return mixed
     */
    public function setSetting($settingCategoryKey, $settingKey, $settingValue)
    {
        return $this->settingsStorage->setSetting($settingCategoryKey, $settingKey, $settingValue);
    }

    /**
     * @param $settingCategoryKey
     * @param $settingValues
     *
     * @return mixed
     */
    public function setCategorySettings($settingCategoryKey, $settingValues)
    {
        return $this->settingsStorage->setCategorySettings($settingCategoryKey, $settingValues);
    }

    /**
     * @param $settings
     *
     * @return mixed
     */
    public function setAllSettings($settings)
    {
        return $this->settingsStorage->setAllSettings($settings);
    }

    /**
     * @param Json $entitySettingsJson
     *
     * @return Settings
     */
    public function getEntitySettings($entitySettingsJson)
    {
        return SettingsFactory::create($entitySettingsJson, $this->getAllSettingsCategorized());
    }

    /**
     * @param Json $entitySettingsJson
     *
     * @return Settings
     */
    public function getSavedSettings($entitySettingsJson)
    {
        $data = $entitySettingsJson ? json_decode($entitySettingsJson->getValue(), true) : [];

        $isOldEntitySettings = !isset($data['activation']['version']);

        if ($isOldEntitySettings && isset($data['general']['minimumTimeRequirementPriorToCanceling'])) {
            $data['general']['minimumTimeRequirementPriorToRescheduling'] =
                $data['general']['minimumTimeRequirementPriorToCanceling'];
        }

        return $data;
    }

    /**
     * @param array $entities
     *
     * @return void
     */
    public function setStashEntities($entities)
    {
        update_option('amelia_stash', json_encode($entities));
    }

    /**
     * @return array
     */
    public function getStashEntities()
    {
        $entitiesStash = get_option('amelia_stash');

        return $entitiesStash ? json_decode($entitiesStash, true) : [];
    }

    /**
     * @param array $settings
     *
     * @return void
     */
    public function fixCustomization(&$settings)
    {
        if (isset($settings['forms']) && $settings['forms']) {
            foreach ($settings['forms'] as $formName => &$form) {
                if (isset($form['confirmBookingForm'])) {
                    foreach ($form['confirmBookingForm'] as $entityName => &$entity) {
                        if (isset($entity['itemsStatic']['paymentMethodFormField']['switchPaymentMethodViewOptions'])) {
                            array_splice(
                                $settings['forms'][$formName]['confirmBookingForm'][$entityName]['itemsStatic']
                                ['paymentMethodFormField']['switchPaymentMethodViewOptions'],
                                2
                            );
                        }
                    }
                }
            }
        }
    }
}
