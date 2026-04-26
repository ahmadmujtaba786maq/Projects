<?php

namespace AmeliaBooking\Domain\Factory\CustomField;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\CustomFieldType;
use AmeliaBooking\Domain\ValueObjects\String\CustomFieldSaveType;
use AmeliaBooking\Domain\ValueObjects\String\Label;

/**
 * Class CustomFieldFactory
 *
 * @package AmeliaBooking\Domain\Factory\CustomField
 */
class CustomFieldFactory
{
    /**
     * @param $data
     *
     * @return CustomField
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public static function create($data)
    {
        $customField = new CustomField(
            new Label($data['label']),
            new CustomFieldType($data['type']),
            new BooleanValueObject($data['required']),
            new IntegerValue($data['position']),
            new IntegerValue($data['width']),
            new CustomFieldSaveType($data['saveType'])
        );

        if (isset($data['id'])) {
            $customField->setId(new Id($data['id']));
        }

        if (isset($data['options'])) {
            $optionList = [];
            /** @var array $options */
            $options = $data['options'];
            foreach ($options as $option) {
                $optionList[] = CustomFieldOptionFactory::create($option);
            }

            $customField->setOptions(new Collection($optionList));
        }

        if (isset($data['translations'])) {
            $customField->setTranslations(new Json($data['translations']));
        }

        $serviceList = [];

        if (isset($data['allServices']) && $data['allServices']) {
            $customField->setAllServices(new BooleanValueObject(true));
        } else {
            $customField->setAllServices(new BooleanValueObject(false));
            if (isset($data['services'])) {
                /** @var array $options */
                $services = $data['services'];
                foreach ($services as $service) {
                    $serviceList[] = ServiceFactory::create($service);
                }
            }
        }

        $customField->setServices(new Collection($serviceList));

        $eventList = [];

        if (isset($data['allEvents']) && $data['allEvents']) {
            $customField->setAllEvents(new BooleanValueObject(true));
        } else {
            $customField->setAllEvents(new BooleanValueObject(false));
            if (isset($data['events'])) {
                /** @var array $options */
                $events = $data['events'];
                foreach ($events as $event) {
                    $eventList[] = EventFactory::create($event);
                }
            }
        }

        $customField->setEvents(new Collection($eventList));

        if (isset($data['useAsLocation'])) {
            $customField->setUseAsLocation(new BooleanValueObject($data['useAsLocation']));
        }

        if (isset($data['saveFirstChoice'])) {
            $customField->setSaveFirstChoice(new BooleanValueObject($data['saveFirstChoice']));
        }

        return $customField;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $customFields = [];

        foreach ($rows as $row) {
            $customFieldId = $row['cf_id'];
            $optionId      = $row['cfo_id'];
            $serviceId     = $row['s_id'];
            $eventId       = $row['e_id'];

            $customFields[$customFieldId]['id']              = $row['cf_id'];
            $customFields[$customFieldId]['label']           = $row['cf_label'];
            $customFields[$customFieldId]['type']            = $row['cf_type'];
            $customFields[$customFieldId]['saveType']        = $row['cf_saveType'];
            $customFields[$customFieldId]['required']        = $row['cf_required'];
            $customFields[$customFieldId]['position']        = $row['cf_position'];
            $customFields[$customFieldId]['translations']    = $row['cf_translations'];
            $customFields[$customFieldId]['allServices']     = $row['cf_allServices'];
            $customFields[$customFieldId]['allEvents']       = $row['cf_allEvents'];
            $customFields[$customFieldId]['useAsLocation']   = $row['cf_useAsLocation'];
            $customFields[$customFieldId]['width']           = $row['cf_width'];
            $customFields[$customFieldId]['saveFirstChoice'] = $row['cf_saveFirstChoice'];


            if ($optionId) {
                $customFields[$customFieldId]['options'][$optionId]['id']            = $row['cfo_id'];
                $customFields[$customFieldId]['options'][$optionId]['customFieldId'] = $row['cfo_custom_field_id'];
                $customFields[$customFieldId]['options'][$optionId]['label']         = $row['cfo_label'];
                $customFields[$customFieldId]['options'][$optionId]['position']      = $row['cfo_position'];
                $customFields[$customFieldId]['options'][$optionId]['translations']  = $row['cfo_translations'];
            }

            if ($serviceId) {
                $customFields[$customFieldId]['services'][$serviceId]['id']          = $row['s_id'];
                $customFields[$customFieldId]['services'][$serviceId]['name']        = $row['s_name'];
                $customFields[$customFieldId]['services'][$serviceId]['description'] = $row['s_description'];
                $customFields[$customFieldId]['services'][$serviceId]['color']       = $row['s_color'];
                $customFields[$customFieldId]['services'][$serviceId]['price']       = $row['s_price'];
                $customFields[$customFieldId]['services'][$serviceId]['status']      = $row['s_status'];
                $customFields[$customFieldId]['services'][$serviceId]['categoryId']  = $row['s_categoryId'];
                $customFields[$customFieldId]['services'][$serviceId]['minCapacity'] = $row['s_minCapacity'];
                $customFields[$customFieldId]['services'][$serviceId]['maxCapacity'] = $row['s_maxCapacity'];
                $customFields[$customFieldId]['services'][$serviceId]['duration']    = $row['s_duration'];
            }

            if ($eventId) {
                $customFields[$customFieldId]['events'][$eventId]['id']       = $row['e_id'];
                $customFields[$customFieldId]['events'][$eventId]['name']     = $row['e_name'];
                $customFields[$customFieldId]['events'][$eventId]['price']    = $row['e_price'];
                $customFields[$customFieldId]['events'][$eventId]['parentId'] = $row['e_parentId'];
            }
        }

        $customFieldsCollection = new Collection();

        foreach ($customFields as $customFieldKey => $customFieldArray) {
            if (!array_key_exists('options', $customFieldArray)) {
                $customFieldArray['options'] = [];
            }

            $customFieldsCollection->addItem(
                self::create($customFieldArray),
                $customFieldKey
            );
        }

        return $customFieldsCollection;
    }
}
