<?php

namespace SmartDato\BrtDocumentoDiRitiro\Generator;

use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Data\BrandingData;
use SmartDato\BrtDocumentoDiRitiro\Data\PickupData;
use SmartDato\BrtDocumentoDiRitiro\Data\ShipmentData;

final class DocumentSpec
{
    /**
     * @param  list<ShipmentData>  $shipments
     */
    public function __construct(
        public readonly PickupData $pickup,
        public readonly AddressData $sender,
        public readonly array $shipments,
        public readonly BrandingData $branding,
        public readonly string $clientCode,
    ) {}
}
