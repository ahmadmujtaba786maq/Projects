<?php

namespace AmeliaBooking\Domain\Factory\CustomField;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\CustomField\CustomFieldOption;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\Label;

/**
 * Class CustomFieldOptionFactory
 *
 * @package AmeliaBooking\Domain\Factory\CustomField
 */
class CustomFieldOptionFactory
{
    /**
     * @param $data
     *
     * @return CustomFieldOption
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $customFieldOption = new CustomFieldOption(
            new Id($data['customFieldId']),
            new Label($data['label']),
            new IntegerValue($data['position'])
        );

        if (isset($data['translations'])) {
            $customFieldOption->setTranslations(new Json($data['translations']));
        }

        if (isset($data['id'])) {
            $customFieldOption->setId(new Id($data['id']));
        }

        return $customFieldOption;
    }
}
