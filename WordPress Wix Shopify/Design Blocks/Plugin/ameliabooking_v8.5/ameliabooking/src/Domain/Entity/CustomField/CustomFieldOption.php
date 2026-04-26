<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\CustomField;

use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\Label;

/**
 * Class CustomFieldOption
 *
 * @package AmeliaBooking\Domain\Entity\CustomField
 */
class CustomFieldOption
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $customFieldId;

    /** @var Label */
    private $label;

    /** @var IntegerValue */
    private $position;

    /** @var  Json */
    private $translations;

    /**
     * CustomFieldOption constructor.
     *
     * @param Id           $customFieldId
     * @param Label        $label
     * @param IntegerValue $position
     */
    public function __construct(Id $customFieldId, Label $label, IntegerValue $position)
    {
        $this->customFieldId = $customFieldId;
        $this->label         = $label;
        $this->position      = $position;
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
     * @return Id
     */
    public function getCustomFieldId()
    {
        return $this->customFieldId;
    }

    /**
     * @param Id $customFieldId
     */
    public function setCustomFieldId($customFieldId)
    {
        $this->customFieldId = $customFieldId;
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
     * @return array
     */
    public function toArray()
    {
        return [
            'id'            => null !== $this->getId() ? $this->getId()->getValue() : null,
            'customFieldId' => $this->getCustomFieldId()->getValue(),
            'label'         => $this->getLabel()->getValue(),
            'position'      => $this->getPosition()->getValue(),
            'translations'  => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
        ];
    }
}
