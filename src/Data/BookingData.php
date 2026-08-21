<?php

namespace SmartDato\BrtDocumentoDiRitiro\Data;

use Spatie\LaravelData\Data;

class BookingData extends Data
{
    /**
     * @param  string  $reference  the pickup reference printed in the VAORFA field
     * @param  int  $referenceLength  width of the VAORFA field
     * @param  string  $clientCode  BRT client code (codex) for the VAOCOR field
     * @param  ?string  $exportedAt  datetime of the export, defaults to now when unparsable
     * @param  ?string  $pickupAfter  requested pickup datetime
     * @param  array<int, string>  $senderStreetLines  pre-split sender street lines (max 35 chars each)
     * @param  ?AddressData  $consignee  hub or direct-delivery consignee block; blank block when null
     * @param  bool  $requestSms  whether BRT should notify the sender via SMS (VAOSMSR/VAOSMSRF)
     */
    public function __construct(
        public string $reference,
        public int $referenceLength,
        public string $clientCode,
        public ?string $exportedAt,
        public ?string $pickupAfter,
        public AddressData $sender,
        public array $senderStreetLines,
        public float|int|null $weight,
        public float|int|null $volume,
        public int $parcelCount,
        public ?int $palletCount = null,
        public ?string $comment = null,
        public ?AddressData $consignee = null,
        public ?string $confirmEmail = null,
        public ?string $openingMorningFrom = null,
        public ?string $openingMorningTo = null,
        public ?string $openingAfternoonFrom = null,
        public ?string $openingAfternoonTo = null,
        public bool $requestSms = false,
        public ?string $smsPhone = null,
    ) {}
}
