<?php

use SmartDato\BrtDocumentoDiRitiro\Support\BorderoGrouper;
use SmartDato\BrtDocumentoDiRitiro\Tests\Fixtures\DocumentFixtures;

test('groups shipments with the same delivery address', function (): void {
    $sameAddress = DocumentFixtures::deliveryAddress();
    $otherAddress = DocumentFixtures::deliveryAddress(['street' => 'Via Milano 5', 'city' => 'MILANO', 'zipcode' => '20100']);

    $shipment1 = DocumentFixtures::shipment(['deliveryAddress' => $sameAddress]);
    $shipment2 = DocumentFixtures::shipment(['deliveryAddress' => $sameAddress]);
    $shipment3 = DocumentFixtures::shipment(['deliveryAddress' => $otherAddress]);

    $grouper = new BorderoGrouper([$shipment1, $shipment2, $shipment3]);

    expect($grouper->groups())->toHaveKeys([$shipment1->key, $shipment3->key])
        ->and($grouper->groups()[$shipment1->key])->toHaveCount(2)
        ->and($grouper->groups()[$shipment3->key])->toHaveCount(1)
        ->and($grouper->shipmentFor($shipment1->key))->toBe($shipment1);
});

test('measurementFor sums weight and volume of the group parcels', function (): void {
    $address = DocumentFixtures::deliveryAddress();

    $shipment1 = DocumentFixtures::shipment([
        'deliveryAddress' => $address,
        'parcels' => [DocumentFixtures::parcel(['weight' => 3.5, 'volume' => 0.01])],
    ]);
    $shipment2 = DocumentFixtures::shipment([
        'deliveryAddress' => $address,
        'parcels' => [DocumentFixtures::parcel(['weight' => 6.5, 'volume' => 0.02])],
    ]);

    $grouper = new BorderoGrouper([$shipment1, $shipment2]);

    expect($grouper->measurementFor($shipment1->key))->toMatchArray(['weight' => 10.0, 'volume' => 0.03]);
});

test('pageCountFor sums the declared parcel counts of same-address shipments', function (): void {
    $address = DocumentFixtures::deliveryAddress();

    $shipment1 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'declaredParcelCount' => 2]);
    $shipment2 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'declaredParcelCount' => 3]);

    $grouper = new BorderoGrouper([$shipment1, $shipment2]);

    expect($grouper->pageCountFor($shipment1))->toBe(5);
});

test('codFor sums cash on delivery for same-address shipments', function (): void {
    $address = DocumentFixtures::deliveryAddress();

    $shipment1 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'cashOnDelivery' => 50.00]);
    $shipment2 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'cashOnDelivery' => 30.00]);

    $grouper = new BorderoGrouper([$shipment1, $shipment2]);

    expect($grouper->codFor($shipment1))->toEqual(80.00);
});

test('codFor returns zero when the queried shipment itself has no cash on delivery', function (): void {
    $address = DocumentFixtures::deliveryAddress();

    $shipment1 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'cashOnDelivery' => null]);
    $shipment2 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'cashOnDelivery' => 30.00]);

    $grouper = new BorderoGrouper([$shipment1, $shipment2]);

    expect($grouper->codFor($shipment1))->toEqual(0);
});

test('positionOf returns the 1-based position of a parcel within its group', function (): void {
    $address = DocumentFixtures::deliveryAddress();

    $parcelA = DocumentFixtures::parcel();
    $parcelB = DocumentFixtures::parcel();
    $parcelC = DocumentFixtures::parcel();

    $shipment1 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'parcels' => [$parcelA, $parcelB]]);
    $shipment2 = DocumentFixtures::shipment(['deliveryAddress' => $address, 'parcels' => [$parcelC]]);

    $grouper = new BorderoGrouper([$shipment1, $shipment2]);

    expect($grouper->positionOf($parcelA->key))->toBe(1)
        ->and($grouper->positionOf($parcelB->key))->toBe(2)
        ->and($grouper->positionOf($parcelC->key))->toBe(3)
        ->and($grouper->positionOf('unknown'))->toBeNull();
});
