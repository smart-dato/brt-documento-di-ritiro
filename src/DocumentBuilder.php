<?php

namespace SmartDato\BrtDocumentoDiRitiro;

use InvalidArgumentException;
use SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator;
use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Data\BrandingData;
use SmartDato\BrtDocumentoDiRitiro\Data\PickupData;
use SmartDato\BrtDocumentoDiRitiro\Data\ShipmentData;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\PdfMergeException;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\TooManyParcelsException;
use SmartDato\BrtDocumentoDiRitiro\Generator\DocumentoDiRitiroGenerator;
use SmartDato\BrtDocumentoDiRitiro\Generator\DocumentSpec;

class DocumentBuilder
{
    private ?PickupData $pickup = null;

    private ?AddressData $sender = null;

    /** @var list<ShipmentData> */
    private array $shipments = [];

    private ?BrandingData $branding = null;

    private ?string $clientCode = null;

    public function __construct(private SegnacolloAllocator $allocator) {}

    public function pickup(PickupData $pickup): self
    {
        $this->pickup = $pickup;

        return $this;
    }

    public function sender(AddressData $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function addShipment(ShipmentData $shipment): self
    {
        $this->shipments[] = $shipment;

        return $this;
    }

    public function branding(BrandingData $branding): self
    {
        $this->branding = $branding;

        return $this;
    }

    public function clientCode(string $clientCode): self
    {
        $this->clientCode = $clientCode;

        return $this;
    }

    public function allocator(SegnacolloAllocator $allocator): self
    {
        $this->allocator = $allocator;

        return $this;
    }

    /**
     * @throws TooManyParcelsException
     * @throws PdfMergeException
     */
    public function generate(): GenerationResult
    {
        $this->validate();

        $generator = new DocumentoDiRitiroGenerator($this->allocator);

        return $generator->generate(new DocumentSpec(
            pickup: $this->pickup,
            sender: $this->sender,
            shipments: $this->shipments,
            branding: $this->branding,
            clientCode: $this->clientCode,
        ));
    }

    private function validate(): void
    {
        if ($this->pickup === null) {
            throw new InvalidArgumentException('A pickup is required, call pickup() before generate().');
        }

        if ($this->sender === null) {
            throw new InvalidArgumentException('A sender address is required, call sender() before generate().');
        }

        if ($this->shipments === []) {
            throw new InvalidArgumentException('At least one shipment is required, call addShipment() before generate().');
        }

        if ($this->branding === null) {
            throw new InvalidArgumentException('Branding is required, call branding() before generate().');
        }

        if ($this->clientCode === null) {
            throw new InvalidArgumentException('A BRT client code is required, call clientCode() before generate().');
        }

        foreach ($this->shipments as $shipment) {
            foreach ($shipment->parcels as $parcel) {
                if ($parcel->hasLabel && $parcel->existingLabelPdf === null) {
                    throw new InvalidArgumentException("Parcel {$parcel->key} has a label, but no existingLabelPdf was provided.");
                }
            }
        }
    }
}
