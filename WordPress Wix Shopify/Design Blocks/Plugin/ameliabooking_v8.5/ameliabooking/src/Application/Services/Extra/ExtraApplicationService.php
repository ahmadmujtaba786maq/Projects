<?php

namespace AmeliaBooking\Application\Services\Extra;

use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class ExtraApplicationService
 *
 * @package AmeliaBooking\Application\Services\Extra
 */
class ExtraApplicationService extends AbstractExtraApplicationService
{
    /**
     * @param Service $service
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function manageExtrasForServiceAdd($service)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        if ($service->getExtras() !== null) {
            $extras = $service->getExtras();
            foreach ($extras->getItems() as $extra) {
                /** @var Extra $extra */
                $extra->setServiceId(new Id($service->getId()->getValue()));

                if (!($extraId = $extraRepository->add($extra))) {
                    $serviceRepository->rollback();
                }

                $extra->setId(new Id($extraId));
            }
        }
    }

    /**
     * @param Service $service
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function manageExtrasForServiceUpdate($service)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        if ($service->getExtras() !== null) {
            $extras = $service->getExtras();
            foreach ($extras->getItems() as $extra) {
                /** @var Extra $extra */
                $extra->setServiceId(new Id($service->getId()->getValue()));
                if ($extra->getId() === null) {
                    if (!($extraId = $extraRepository->add($extra))) {
                        $serviceRepository->rollback();
                    }

                    $extra->setId(new Id($extraId));
                } else {
                    if (!$extraRepository->update($extra->getId()->getValue(), $extra)) {
                        $serviceRepository->rollback();
                    }
                }
            }
        }
    }
}
