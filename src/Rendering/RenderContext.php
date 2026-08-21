<?php

namespace SmartDato\BrtDocumentoDiRitiro\Rendering;

use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Data\BrandingData;
use SmartDato\BrtDocumentoDiRitiro\Data\ParcelData;
use SmartDato\BrtDocumentoDiRitiro\Data\PickupData;
use SmartDato\BrtDocumentoDiRitiro\Data\ShipmentData;

final class RenderContext
{
    /**
     * @param  ?array{weight: float|int, volume: float|int}  $measurement
     */
    public function __construct(
        public readonly PickupData $pickup,
        public readonly AddressData $sender,
        public readonly ShipmentData $shipment,
        public readonly ?ParcelData $parcel,
        public readonly BrandingData $branding,
        public readonly string $clientCode,
        public readonly string $sideText,
        public readonly ?array $measurement,
        public readonly ?int $pageCounter,
        public readonly float|int $factor,
    ) {}
}
