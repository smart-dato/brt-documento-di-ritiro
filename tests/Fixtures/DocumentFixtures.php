<?php

namespace SmartDato\BrtDocumentoDiRitiro\Tests\Fixtures;

use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Data\BrandingData;
use SmartDato\BrtDocumentoDiRitiro\Data\ParcelData;
use SmartDato\BrtDocumentoDiRitiro\Data\PickupData;
use SmartDato\BrtDocumentoDiRitiro\Data\ShipmentData;
use SmartDato\BrtDocumentoDiRitiro\Models\SegnacolloRange;

class DocumentFixtures
{
    private static int $sequence = 0;

    public static function senderAddress(array $overrides = []): AddressData
    {
        return new AddressData(...array_merge([
            'personName' => 'Sender Name',
            'companyName' => 'OMEST SRL',
            'street' => 'Via L. Negrelli Straße 15',
            'zipcode' => '39100',
            'city' => 'BOLZANO',
            'countryCode' => 'it',
            'countryName' => 'Italy',
            'phone' => '+39 0471 1962318',
            'email' => 'info@europacco.com',
        ], $overrides));
    }

    public static function deliveryAddress(array $overrides = []): AddressData
    {
        return new AddressData(...array_merge([
            'personName' => 'Mario Rossi',
            'companyName' => 'Test SRL',
            'street' => 'Via Roma 1',
            'zipcode' => '39100',
            'city' => 'BOLZANO',
            'countryCode' => 'it',
            'countryName' => 'Italy',
            'phone' => '+39 0471 000000',
            'email' => 'test@example.com',
        ], $overrides));
    }

    public static function branding(array $overrides = []): BrandingData
    {
        return new BrandingData(...array_merge([
            'termsUrl' => 'www.example.com',
            'sideLogoPath' => null,
            'fallbackRecipientName' => null,
        ], $overrides));
    }

    public static function pickup(array $overrides = []): PickupData
    {
        return new PickupData(...array_merge([
            'documentNumber' => 'PU-TEST-'.self::nextSequence(),
            'documentDate' => '2026-08-21',
        ], $overrides));
    }

    public static function parcel(array $overrides = []): ParcelData
    {
        return new ParcelData(...array_merge([
            'key' => 'PARCEL-'.self::nextSequence(),
            'weight' => 5.0,
            'volume' => 0.024,
        ], $overrides));
    }

    public static function shipment(array $overrides = [], int $parcelCount = 1): ShipmentData
    {
        $parcels = $overrides['parcels'] ?? array_map(fn () => self::parcel(), range(1, $parcelCount));

        return new ShipmentData(...array_merge([
            'key' => 'SHIPMENT-'.self::nextSequence(),
            'deliveryAddress' => self::deliveryAddress(),
            'onBehalfOf' => 'Europacco',
            'cashOnDelivery' => null,
            'codTypeName' => null,
            'declaredParcelCount' => count($parcels),
        ], $overrides, ['parcels' => $parcels]));
    }

    public static function seedRange(
        string $from = '10000000000010',
        string $to = '10000001000002',
        ?string $next = null,
    ): SegnacolloRange {
        return SegnacolloRange::create([
            'from' => $from,
            'to' => $to,
            'next' => $next,
        ]);
    }

    private static function nextSequence(): int
    {
        return ++self::$sequence;
    }
}
