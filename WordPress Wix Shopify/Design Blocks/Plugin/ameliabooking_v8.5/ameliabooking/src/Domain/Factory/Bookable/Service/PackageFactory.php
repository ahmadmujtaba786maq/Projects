<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\DiscountPercentageValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\DepositType;
use AmeliaBooking\Domain\ValueObjects\String\EntityType;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Status;

/**
 * Class PackageFactory
 *
 * @package AmeliaBooking\Domain\Factory\Bookable\Service
 */
class PackageFactory
{
    /**
     * @param $data
     *
     * @return Package
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        /** @var Package $package */
        $package = new Package();

        if (isset($data['id'])) {
            $package->setId(new Id($data['id']));
        }

        if (isset($data['name'])) {
            $package->setName(new Name($data['name']));
        }

        if (isset($data['price'])) {
            $package->setPrice(new Price($data['price']));
        }

        if (isset($data['description'])) {
            $package->setDescription(new Description($data['description']));
        }

        if (isset($data['color'])) {
            $package->setColor(new Color($data['color']));
        }

        if (!empty($data['pictureFullPath']) && !empty($data['pictureThumbPath'])) {
            $package->setPicture(new Picture($data['pictureFullPath'], $data['pictureThumbPath']));
        }

        if (!empty($data['position'])) {
            $package->setPosition(new PositiveInteger($data['position']));
        }

        if (!empty($data['status'])) {
            $package->setStatus(new Status($data['status']));
        }

        if (isset($data['calculatedPrice'])) {
            $package->setCalculatedPrice(new BooleanValueObject($data['calculatedPrice']));
        }

        if (isset($data['discount'])) {
            $package->setDiscount(new DiscountPercentageValue($data['discount']));
        }

        if (!empty($data['settings'])) {
            $package->setSettings(new Json($data['settings']));
        }

        if (!empty($data['endDate'])) {
            $package->setEndDate(
                new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['endDate']))
            );
        }

        if (!empty($data['durationCount'])) {
            $package->setDurationCount(new PositiveInteger($data['durationCount']));
        }

        if (!empty($data['durationType'])) {
            $package->setDurationType(new Label($data['durationType']));
        }

        if (isset($data['deposit'])) {
            $package->setDeposit(new Price($data['deposit']));
        }

        if (isset($data['depositPayment'])) {
            $package->setDepositPayment(new DepositType($data['depositPayment']));
        }

        if (isset($data['fullPayment'])) {
            $package->setFullPayment(new BooleanValueObject($data['fullPayment']));
        }

        if (isset($data['limitPerCustomer'])) {
            $package->setLimitPerCustomer(new Json($data['limitPerCustomer']));
        }

        /** @var Collection $gallery */
        $gallery = new Collection();

        if (!empty($data['gallery'])) {
            foreach ((array)$data['gallery'] as $image) {
                $galleryImage = new GalleryImage(
                    new EntityType(Entities::PACKAGE),
                    new Picture($image['pictureFullPath'], $image['pictureThumbPath']),
                    new PositiveInteger($image['position'])
                );

                if (!empty($image['id'])) {
                    $galleryImage->setId(new Id($image['id']));
                }

                if ($package->getId()) {
                    $galleryImage->setEntityId($package->getId());
                }

                $gallery->addItem($galleryImage);
            }
        }

        $package->setGallery($gallery);

        if (!empty($data['translations'])) {
            $package->setTranslations(new Json($data['translations']));
        }

        if (isset($data['sharedCapacity'])) {
            $package->setSharedCapacity(new BooleanValueObject($data['sharedCapacity']));
        }

        if (isset($data['quantity'])) {
            $package->setQuantity(new PositiveInteger($data['quantity']));
        }

        /** @var Collection $bookable */
        $bookable = new Collection();

        if (!empty($data['bookable'])) {
            foreach ($data['bookable'] as $key => $value) {
                $bookable->addItem(PackageServiceFactory::create($value), $key);
            }
        }

        $package->setBookable($bookable);

        return $package;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $packages = [];

        foreach ($rows as $row) {
            $packageId  = $row['package_id'];
            $bookableId = $row['package_service_id'];
            $galleryId  = isset($row['gallery_id']) ? $row['gallery_id'] : null;
            $providerId = isset($row['provider_id']) ? $row['provider_id'] : null;
            $locationId = isset($row['location_id']) ? $row['location_id'] : null;

            $packages[$packageId]['id']          = $row['package_id'];
            $packages[$packageId]['name']        = $row['package_name'];
            $packages[$packageId]['description'] = $row['package_description'];
            $packages[$packageId]['color']       = $row['package_color'];
            $packages[$packageId]['price']       = $row['package_price'];
            $packages[$packageId]['status']      = $row['package_status'];
            $packages[$packageId]['pictureFullPath']  = $row['package_picture_full'];
            $packages[$packageId]['pictureThumbPath'] = $row['package_picture_thumb'];
            $packages[$packageId]['position']         = isset($row['package_position']) ? $row['package_position'] : 0;
            $packages[$packageId]['calculatedPrice']  = $row['package_calculated_price'];
            $packages[$packageId]['sharedCapacity']   = isset($row['package_sharedCapacity']) ?
                $row['package_sharedCapacity'] : null;
            $packages[$packageId]['quantity']         = isset($row['package_quantity']) ?
                $row['package_quantity'] : null;
            $packages[$packageId]['discount']         = $row['package_discount'];
            $packages[$packageId]['settings']         = $row['package_settings'];
            $packages[$packageId]['endDate']          = $row['package_endDate'];
            $packages[$packageId]['durationCount']    = $row['package_durationCount'];
            $packages[$packageId]['durationType']     = $row['package_durationType'];
            $packages[$packageId]['translations']     = $row['package_translations'];
            $packages[$packageId]['deposit']          = isset($row['package_deposit']) ? $row['package_deposit'] : null;
            $packages[$packageId]['depositPayment']   = isset($row['package_depositPayment']) ?
                $row['package_depositPayment'] : null;
            $packages[$packageId]['fullPayment']      = isset($row['package_fullPayment']) ?
                $row['package_fullPayment'] : 0;
            $packages[$packageId]['limitPerCustomer'] = isset($row['package_limitPerCustomer']) ?
                $row['package_limitPerCustomer'] : null;

            if ($bookableId) {
                $packages[$packageId]['bookable'][$bookableId]['id']            = $bookableId;
                $packages[$packageId]['bookable'][$bookableId]['service']['id'] = $row['service_id'];
                $packages[$packageId]['bookable'][$bookableId]['service']['name'] = $row['service_name'];
                $packages[$packageId]['bookable'][$bookableId]['service']['description'] =
                    !empty($row['service_description']) ?
                        $row['service_description'] :
                        null;
                $packages[$packageId]['bookable'][$bookableId]['service']['status']          = $row['service_status'];
                $packages[$packageId]['bookable'][$bookableId]['service']['categoryId']      = $row['service_categoryId'];
                $packages[$packageId]['bookable'][$bookableId]['service']['duration']        = $row['service_duration'];
                $packages[$packageId]['bookable'][$bookableId]['service']['timeBefore']      = $row['service_timeBefore'];
                $packages[$packageId]['bookable'][$bookableId]['service']['timeAfter']       = $row['service_timeAfter'];
                $packages[$packageId]['bookable'][$bookableId]['service']['price']           = $row['service_price'];
                $packages[$packageId]['bookable'][$bookableId]['service']['minCapacity']     = $row['service_minCapacity'];
                $packages[$packageId]['bookable'][$bookableId]['service']['maxCapacity']     = $row['service_maxCapacity'];
                $packages[$packageId]['bookable'][$bookableId]['service']['pictureFullPath'] = $row['service_picture_full'];
                $packages[$packageId]['bookable'][$bookableId]['service']['pictureThumbPath'] = $row['service_picture_thumb'];
                $packages[$packageId]['bookable'][$bookableId]['service']['translations']     =
                    !empty($row['service_translations']) ?
                        $row['service_translations'] :
                        null;
                $packages[$packageId]['bookable'][$bookableId]['quantity']         = $row['package_service_quantity'];
                $packages[$packageId]['bookable'][$bookableId]['minimumScheduled'] = $row['package_service_minimumScheduled'];
                $packages[$packageId]['bookable'][$bookableId]['maximumScheduled'] = $row['package_service_maximumScheduled'];
                $packages[$packageId]['bookable'][$bookableId]['allowProviderSelection'] = $row['package_service_allowProviderSelection'];
                $packages[$packageId]['bookable'][$bookableId]['position']        = $row['package_service_position'];
                $packages[$packageId]['bookable'][$bookableId]['service']['show'] = !empty($row['service_show']) ? $row['service_show'] : null;
            }

            if ($galleryId) {
                $packages[$packageId]['gallery'][$galleryId]['id'] = $row['gallery_id'];
                $packages[$packageId]['gallery'][$galleryId]['pictureFullPath']  = $row['gallery_picture_full'];
                $packages[$packageId]['gallery'][$galleryId]['pictureThumbPath'] = $row['gallery_picture_thumb'];
                $packages[$packageId]['gallery'][$galleryId]['position']         = $row['gallery_position'];
            }

            if ($providerId) {
                $packages[$packageId]['bookable'][$bookableId]['providers'][$providerId]['id']            = $row['provider_id'];
                $packages[$packageId]['bookable'][$bookableId]['providers'][$providerId]['firstName']     = $row['provider_firstName'];
                $packages[$packageId]['bookable'][$bookableId]['providers'][$providerId]['lastName']      = $row['provider_lastName'];
                $packages[$packageId]['bookable'][$bookableId]['providers'][$providerId]['email']         = $row['provider_email'];
                $packages[$packageId]['bookable'][$bookableId]['providers'][$providerId]['status']        =
                    !empty($row['provider_status']) ?
                        $row['provider_status'] :
                        Status::VISIBLE;
                $packages[$packageId]['bookable'][$bookableId]['providers'][$providerId]['type']          = Entities::PROVIDER;
                $packages[$packageId]['bookable'][$bookableId]['providers'][$providerId]['stripeConnect'] =
                    !empty($row['provider_stripeConnect'])
                        ? json_decode($row['provider_stripeConnect'], true)
                        : null;
            }

            if ($locationId) {
                $packages[$packageId]['bookable'][$bookableId]['locations'][$locationId]['id']        = $row['location_id'];
                $packages[$packageId]['bookable'][$bookableId]['locations'][$locationId]['name']      = $row['location_name'];
                $packages[$packageId]['bookable'][$bookableId]['locations'][$locationId]['address']   = $row['location_address'];
                $packages[$packageId]['bookable'][$bookableId]['locations'][$locationId]['phone']     = $row['location_phone'];
                $packages[$packageId]['bookable'][$bookableId]['locations'][$locationId]['latitude']  = $row['location_latitude'];
                $packages[$packageId]['bookable'][$bookableId]['locations'][$locationId]['longitude'] = $row['location_longitude'];
            }
        }

        $packagesCollection = new Collection();

        foreach ($packages as $packageKey => $packageArray) {
            if (!array_key_exists('gallery', $packageArray)) {
                $packageArray['gallery'] = [];
            }

            $packagesCollection->addItem(
                self::create($packageArray),
                $packageKey
            );
        }

        return $packagesCollection;
    }
}
