<?php

namespace SmartDato\BrtDocumentoDiRitiro\Data;

use Spatie\LaravelData\Data;

class ShipmentData extends Data
{
    /**
     * @param  array<int, ParcelData>  $parcels
     */
    public function __construct(
        public string $key,
        public AddressData $deliveryAddress,
        public string $onBehalfOf,
        public float|string|null $cashOnDelivery = null,
        public ?string $codTypeName = null,
        public int $declaredParcelCount = 0,
        public array $parcels = [],
        public bool $useBarcodeSideLayout = false,
    ) {}
}
