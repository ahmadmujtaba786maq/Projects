<?php

namespace AmeliaBooking\Application\Services\Tax;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxEntityRepository;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxRepository;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class TaxApplicationService
 *
 * @package AmeliaBooking\Application\Services\Tax
 */
class TaxApplicationService extends AbstractTaxApplicationService
{
    /**
     * @param Tax   $tax
     * @param array $entitiesIds
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function setTaxEntities($tax, $entitiesIds)
    {
        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        /** @var TaxEntityRepository $taxEntityRepository */
        $taxEntityRepository = $this->container->get('domain.tax.entity.repository');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var ExtraRepository $extraRepository */
        $extraRepository = $this->container->get('domain.bookable.extra.repository');

        if ($tax->getAllServices() && $tax->getAllServices()->getValue()) {
            $taxEntityRepository->deleteAllForEntities('service', new Collection());

            $taxRepository->updateFieldByColumn('allServices', 0, 'allServices', 1);
        }

        /** @var Collection $services */
        $services = !empty($entitiesIds['services']) && !($tax->getAllServices() && $tax->getAllServices()->getValue())
            ? $serviceRepository->getByIds($entitiesIds['services'])
            : new Collection();

        $tax->setServiceList($services);

        if ($tax->getAllEvents() && $tax->getAllEvents()->getValue()) {
            $taxEntityRepository->deleteAllForEntities('event', new Collection());

            $taxRepository->updateFieldByColumn('allEvents', 0, 'allEvents', 1);
        }

        /** @var Collection $events */
        $events = !empty($entitiesIds['events']) && !($tax->getAllEvents() && $tax->getAllEvents()->getValue())
            ? $eventRepository->getByIds($entitiesIds['events'])
            : new Collection();

        $tax->setEventList($events);

        if ($tax->getAllPackages() && $tax->getAllPackages()->getValue()) {
            $taxEntityRepository->deleteAllForEntities('package', new Collection());

            $taxRepository->updateFieldByColumn('allPackages', 0, 'allPackages', 1);
        }

        /** @var Collection $packages */
        $packages = !empty($entitiesIds['packages']) && !($tax->getAllPackages() && $tax->getAllPackages()->getValue())
            ? $packageRepository->getByIds($entitiesIds['packages'])
            : new Collection();

        $tax->setPackageList($packages);

        if ($tax->getAllExtras() && $tax->getAllExtras()->getValue()) {
            $taxEntityRepository->deleteAllForEntities('extra', new Collection());

            $taxRepository->updateFieldByColumn('allExtras', 0, 'allExtras', 1);
        }

        /** @var Collection $extras */
        $extras = !empty($entitiesIds['extras']) && !($tax->getAllExtras() && $tax->getAllExtras()->getValue())
            ? $extraRepository->getByIds($entitiesIds['extras'])
            : new Collection();

        $tax->setExtraList($extras);
    }

    /**
     * @param Tax $tax
     *
     * @return int
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function add($tax)
    {
        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        $taxId = $taxRepository->add($tax);

        $tax->setId(new Id($taxId));

        $this->manageEntities($tax);

        return (int)$taxId;
    }

    /**
     * @param Tax $tax
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function update($tax)
    {
        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        $taxRepository->update($tax->getId()->getValue(), $tax);

        $this->manageEntities($tax);
    }

    /**
     * @param Tax $tax
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function delete($tax)
    {
        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        /** @var TaxEntityRepository $taxEntityRepository */
        $taxEntityRepository = $this->container->get('domain.tax.entity.repository');

        /** @var CustomerBookingRepository $customerBookingRepository */
        $customerBookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        return $taxEntityRepository->deleteByEntityId($tax->getId()->getValue(), 'taxId') &&
            $customerBookingRepository->updateByEntityId($tax->getId()->getValue(), null, 'tax') &&
            $packageCustomerRepository->updateByEntityId($tax->getId()->getValue(), null, 'tax') &&
            $taxRepository->delete($tax->getId()->getValue());
    }

    /**
     * @param Tax $tax
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    public function manageEntities($tax)
    {
        /** @var TaxEntityRepository $taxEntityRepository */
        $taxEntityRepository = $this->container->get('domain.tax.entity.repository');

        $taxEntityRepository->deleteByEntityId($tax->getId()->getValue(), 'taxId');

        if ($tax->getServiceList()->length()) {
            $taxEntityRepository->deleteAllForEntities(Entities::SERVICE, $tax->getServiceList());

            /** @var Service $service */
            foreach ($tax->getServiceList()->getItems() as $service) {
                $taxEntityRepository->add($tax, $service);
            }
        }

        if ($tax->getExtraList()->length()) {
            $taxEntityRepository->deleteAllForEntities(Entities::EXTRA, $tax->getExtraList());

            /** @var Extra $extra */
            foreach ($tax->getExtraList()->getItems() as $extra) {
                $taxEntityRepository->add($tax, $extra);
            }
        }

        if ($tax->getEventList()->length()) {
            $taxEntityRepository->deleteAllForEntities(Entities::EVENT, $tax->getEventList());

            /** @var Event $event */
            foreach ($tax->getEventList()->getItems() as $event) {
                $taxEntityRepository->add($tax, $event);
            }
        }

        if ($tax->getPackageList()->length()) {
            $taxEntityRepository->deleteAllForEntities(Entities::PACKAGE, $tax->getPackageList());

            /** @var Package $package */
            foreach ($tax->getPackageList()->getItems() as $package) {
                $taxEntityRepository->add($tax, $package);
            }
        }
    }

    /**
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getAll()
    {
        /** @var TaxRepository $taxRepository */
        $taxRepository = $this->container->get('domain.tax.repository');

        return $taxRepository->getWithEntities([]);
    }

    /**
     * @param int        $id
     * @param string     $type
     * @param Collection $taxes
     *
     * @return string|null
     */
    public function getTaxData($id, $type, $taxes)
    {
        $taxData = [];

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $taxesSettings = $settingsService->getSetting(
            'payments',
            'taxes'
        );

        /** @var Tax $tax */
        foreach ($taxes->getItems() as $tax) {
            if (
                $tax->getStatus()->getValue() === Status::VISIBLE &&
                (
                    (
                        $type === Entities::SERVICE &&
                        (
                            $tax->getServiceList()->keyExists($id) ||
                            ($tax->getAllServices() && $tax->getAllServices()->getValue())
                        )
                    ) ||
                    (
                        $type === Entities::EXTRA &&
                        (
                            $tax->getExtraList()->keyExists($id) ||
                            ($tax->getAllExtras() && $tax->getAllExtras()->getValue())
                        )
                    ) ||
                    (
                        $type === Entities::PACKAGE &&
                        (
                            $tax->getPackageList()->keyExists($id) ||
                            ($tax->getAllPackages() && $tax->getAllPackages()->getValue())
                        )
                    ) ||
                    (
                        $type === Entities::EVENT &&
                        (
                            $tax->getEventList()->keyExists($id) ||
                            ($tax->getAllEvents() && $tax->getAllEvents()->getValue())
                        )
                    )
                )
            ) {
                $taxData[] = [
                    'name'     => $tax->getName()->getValue(),
                    'amount'   => $tax->getAmount()->getValue(),
                    'type'     => $tax->getType()->getValue(),
                    'excluded' => $taxesSettings['excluded'],
                ];
            }
        }

        return $taxData ? json_encode($taxData) : null;
    }

    /**
     * @param float $value
     * @param Tax   $tax
     *
     * @return float
     */
    public function getBasePrice($value, $tax)
    {
        switch ($tax->getType()->getValue()) {
            case ('percentage'):
                return $value / (1 + (float)$tax->getAmount()->getValue() / 100);
            case ('fixed'):
                return $value - (float)$tax->getAmount()->getValue();
        }

        return 0;
    }
}
