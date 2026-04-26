<?php

namespace AmeliaSabre\VObject\Property\VCard;

use AmeliaSabre\VObject\Property;

/**
 * PhoneNumber property.
 *
 * This object encodes PHONE-NUMBER values.
 *
 * @author Christian Kraus <christian@kraus.work>
 */
class PhoneNumber extends Property\Text
{
    protected $structuredValues = [];

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    public function getValueType()
    {
        return 'PHONE-NUMBER';
    }
}
