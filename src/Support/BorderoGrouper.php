<?php

namespace SmartDato\BrtDocumentoDiRitiro\Support;

use SmartDato\BrtDocumentoDiRitiro\Data\ParcelData;
use SmartDato\BrtDocumentoDiRitiro\Data\ShipmentData;

class BorderoGrouper
{
    /** @var array<string, ShipmentData> group key (first shipment key) => representative shipment */
    private array $groupShipments = [];

    /** @var array<string, list<array{shipment: ShipmentData, parcel: ParcelData}>> */
    private array $groups = [];

    /**
     * @param  list<ShipmentData>  $shipments
     */
    public function __construct(private readonly array $shipments)
    {
        foreach ($this->shipments as $shipment) {
            $groupKey = $this->groupKeyFor($shipment);
            foreach ($shipment->parcels as $parcel) {
                $this->groups[$groupKey][] = ['shipment' => $shipment, 'parcel' => $parcel];
            }
        }
    }

    /**
     * @return array<string, list<array{shipment: ShipmentData, parcel: ParcelData}>>
     */
    public function groups(): array
    {
        return $this->groups;
    }

    public function shipmentFor(string $groupKey): ShipmentData
    {
        return $this->groupShipments[$groupKey];
    }

    /**
     * @return array{weight: float|int, volume: float|int}
     */
    public function measurementFor(string $groupKey): array
    {
        $weight = 0;
        $volume = 0;
        foreach ($this->groups[$groupKey] ?? [] as $entry) {
            $weight += $entry['parcel']->weight;
            $volume += $entry['parcel']->volume;
        }

        return [
            'weight' => $weight,
            'volume' => $volume,
        ];
    }

    public function codFor(ShipmentData $shipment): float|int
    {
        $cod = 0;
        foreach ($this->shipments as $search) {
            if ($shipment->deliveryAddress->equals($search->deliveryAddress) && strlen((string) $shipment->cashOnDelivery) > 0) {
                $cod += (float) $search->cashOnDelivery;
            }
        }

        return $cod;
    }

    public function pageCountFor(ShipmentData $shipment): int
    {
        $count = 0;
        foreach ($this->shipments as $search) {
            if ($shipment->deliveryAddress->equals($search->deliveryAddress)) {
                $count += $search->declaredParcelCount;
            }
        }

        return $count;
    }

    public function positionOf(string $parcelKey): ?int
    {
        foreach ($this->groups as $entries) {
            foreach ($entries as $index => $entry) {
                if ($entry['parcel']->key == $parcelKey) {
                    return $index + 1;
                }
            }
        }

        return null;
    }

    private function groupKeyFor(ShipmentData $shipment): string
    {
        foreach ($this->groupShipments as $groupKey => $groupShipment) {
            if ($shipment->deliveryAddress->equals($groupShipment->deliveryAddress)) {
                return $groupKey;
            }
        }

        $this->groupShipments[$shipment->key] = $shipment;

        return $shipment->key;
    }
}
