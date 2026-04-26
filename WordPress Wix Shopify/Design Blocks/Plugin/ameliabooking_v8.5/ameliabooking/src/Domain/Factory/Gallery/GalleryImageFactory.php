<?php

namespace AmeliaBooking\Domain\Factory\Gallery;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\EntityType;

/**
 * Class GalleryImageFactory
 *
 * @package AmeliaBooking\Domain\Factory\CustomField
 */
class GalleryImageFactory
{
    /**
     * @param $data
     *
     * @return GalleryImage
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $galleryImage = new GalleryImage(
            new EntityType($data['entityType']),
            new Picture($data['pictureFullPath'], $data['pictureThumbPath']),
            new PositiveInteger($data['position'])
        );

        if (isset($data['id'])) {
            $galleryImage->setId(new Id($data['id']));
        }

        if (isset($data['entityId'])) {
            $galleryImage->setEntityId(new Id($data['entityId']));
        }

        if (isset($data['entityType'])) {
            $galleryImage->setEntityType(new EntityType($data['entityType']));
        }

        return $galleryImage;
    }
}
