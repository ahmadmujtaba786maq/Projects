<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\CustomField;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\CustomFieldType;
use AmeliaBooking\Domain\ValueObjects\String\CustomFieldSaveType;
use AmeliaBooking\Domain\ValueObjects\String\Label;

/**
 * Class CustomField
 *
 * @package AmeliaBooking\Domain\Entity\CustomField
 */
class CustomField
{
    /** @var Id */
    private $id;

    /** @var Label */
    private $label;

    /** @var CustomFieldType */
    private $type;

    /** @var CustomFieldSaveType */
    private $saveType;

    /** @var BooleanValueObject */
    private $required;

    /** @var IntegerValue */
    private $position;

    /** @var  Json */
    private $translations;

    /** @var Collection */
    private $options;

    /** @var Collection */
    private $services;

    /** @var Collection */
    private $events;

    /** @var BooleanValueObject */
    private $allServices;

    /** @var BooleanValueObject */
    private $allEvents;

    /** @var BooleanValueObject */
    private $useAsLocation;

    /** @var BooleanValueObject */
    private $saveFirstChoice;

    /** @var IntegerValue */
    private $width;

    /**
     * CustomField constructor.
     *
     * @param Label               $label
     * @param CustomFieldType     $type
     * @param BooleanValueObject  $required
     * @param IntegerValue        $position
     * @param IntegerValue        $width
     * @param CustomFieldSaveType $saveType
     */
    public function __construct(
        Label $label,
        CustomFieldType $type,
        BooleanValueObject $required,
        IntegerValue $position,
        IntegerValue $width,
        CustomFieldSaveType $saveType
    ) {
        $this->label    = $label;
        $this->type     = $type;
        $this->required = $required;
        $this->position = $position;
        $this->width    = $width;
        $this->saveType = $saveType;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Id $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param Label $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return CustomFieldType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param CustomFieldType $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return CustomFieldSaveType
     */
    public function getSaveType()
    {
        return $this->saveType;
    }

    /**
     * @param CustomFieldSaveType $saveType
     */
    public function setSaveType($saveType)
    {
        $this->saveType = $saveType;
    }

    /**
     * @return BooleanValueObject
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param BooleanValueObject $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return IntegerValue
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param IntegerValue $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return Json
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param Json $translations
     */
    public function setTranslations(Json $translations)
    {
        $this->translations = $translations;
    }

    /**
     * @return Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Collection $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return Collection
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @param Collection $services
     */
    public function setServices($services)
    {
        $this->services = $services;
    }

    /**
     * @return Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param Collection $events
     */
    public function setEvents($events)
    {
        $this->events = $events;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAllServices()
    {
        return $this->allServices;
    }

    /**
     * @param BooleanValueObject $allServices
     */
    public function setAllServices($allServices)
    {
        $this->allServices = $allServices;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAllEvents()
    {
        return $this->allEvents;
    }

    /**
     * @param BooleanValueObject $allEvents
     */
    public function setAllEvents($allEvents)
    {
        $this->allEvents = $allEvents;
    }

    /**
     * @return BooleanValueObject
     */
    public function getUseAsLocation()
    {
        return $this->useAsLocation;
    }

    /**
     * @param BooleanValueObject $useAsLocation
     */
    public function setUseAsLocation($useAsLocation)
    {
        $this->useAsLocation = $useAsLocation;
    }

    /**
     * @return BooleanValueObject
     */
    public function getSaveFirstChoice()
    {
        return $this->saveFirstChoice;
    }

    /**
     * @param BooleanValueObject $saveFirstChoice
     */
    public function setSaveFirstChoice($saveFirstChoice)
    {
        $this->saveFirstChoice = $saveFirstChoice;
    }

    /**
     * @return IntegerValue
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param IntegerValue $width
     */
    public function setWidth($width)
    {
        $this->position = $width;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'              => null !== $this->getId() ? $this->getId()->getValue() : null,
            'label'           => $this->getLabel()->getValue(),
            'type'            => $this->getType()->getValue(),
            'required'        => $this->getRequired()->getValue(),
            'position'        => $this->getPosition()->getValue(),
            'options'         => $this->getOptions() ? $this->getOptions()->toArray() : [],
            'services'        => $this->getServices() ? $this->getServices()->toArray() : [],
            'events'          => $this->getEvents() ? $this->getEvents()->toArray() : [],
            'translations'    => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
            'allServices'     => $this->getAllServices() ? $this->getAllServices()->getValue() : null,
            'allEvents'       => $this->getAllEvents() ? $this->getAllEvents()->getValue() : null,
            'useAsLocation'   => $this->getUseAsLocation() ? $this->getUseAsLocation()->getValue() : null,
            'width'           => $this->getWidth() ? $this->getWidth()->getValue() : 50,
            'saveType'        => $this->getSaveType()->getValue(),
            'saveFirstChoice' => $this->getSaveFirstChoice() ? $this->getSaveFirstChoice()->getValue() : null,
        ];
    }
}
