<?php

namespace AmeliaBooking\Application\Services\Gallery;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Gallery\GalleryRepository;

/**
 * Class GalleryApplicationService
 *
 * @package AmeliaBooking\Application\Services\Gallery
 */
class GalleryApplicationService
{
    private $container;

    /**
     * GalleryApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Collection $entityGallery
     * @param int        $entityId
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageGalleryForEntityAdd($entityGallery, $entityId)
    {
        /** @var GalleryRepository $galleryRepository */
        $galleryRepository = $this->container->get('domain.galleries.repository');

        /** @var GalleryImage $image */
        foreach ($entityGallery->getItems() as $image) {
            $image->setEntityId(new Id($entityId));

            if (!($imageId = $galleryRepository->add($image))) {
                $galleryRepository->rollback();
            }

            $image->setId(new Id($imageId));
        }
    }

    /**
     * @param Collection $entityGallery
     * @param int        $entityId
     * @param string     $entityType
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageGalleryForEntityUpdate($entityGallery, $entityId, $entityType)
    {
        /** @var GalleryRepository $galleryRepository */
        $galleryRepository = $this->container->get('domain.galleries.repository');

        $imagesIds = [];

        /** @var GalleryImage $image */
        foreach ($entityGallery->getItems() as $image) {
            if ($image->getId()) {
                $imagesIds[] = $image->getId()->getValue();
            }
        }

        if (
            !$galleryRepository->deleteAllNotInImagesArray(
                $imagesIds,
                $entityId,
                $entityType
            )
        ) {
            $galleryRepository->rollback();
        }

        /** @var GalleryImage $image */
        foreach ($entityGallery->getItems() as $image) {
            if (!$image->getId()) {
                $galleryRepository->add($image);
            } else {
                $galleryRepository->update($image->getId()->getValue(), $image);
            }
        }
    }

    /**
     * @param Collection $entityGallery
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function manageGalleryForEntityDelete($entityGallery)
    {
        /** @var GalleryRepository $galleryRepository */
        $galleryRepository = $this->container->get('domain.galleries.repository');

        /** @var GalleryImage $image */
        foreach ($entityGallery->getItems() as $image) {
            if (!$galleryRepository->delete($image->getId()->getValue())) {
                $galleryRepository->rollback();
                return false;
            }
        }

        return true;
    }
}
