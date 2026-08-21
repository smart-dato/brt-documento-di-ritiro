<?php

use SmartDato\BrtDocumentoDiRitiro\Facades\BrtDocumentoDiRitiro;
use SmartDato\BrtDocumentoDiRitiro\Tests\Fixtures\DocumentFixtures;

test('generate requires a pickup', function (): void {
    $builder = BrtDocumentoDiRitiro::document()
        ->sender(DocumentFixtures::senderAddress())
        ->addShipment(DocumentFixtures::shipment());

    expect(fn () => $builder->generate())->toThrow(InvalidArgumentException::class, 'pickup');
});

test('generate requires a sender', function (): void {
    $builder = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->addShipment(DocumentFixtures::shipment());

    expect(fn () => $builder->generate())->toThrow(InvalidArgumentException::class, 'sender');
});

test('generate requires at least one shipment', function (): void {
    $builder = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress());

    expect(fn () => $builder->generate())->toThrow(InvalidArgumentException::class, 'shipment');
});

test('generate requires branding', function (): void {
    $builder = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->addShipment(DocumentFixtures::shipment());

    expect(fn () => $builder->generate())->toThrow(InvalidArgumentException::class, 'branding');
});

test('generate requires a client code', function (): void {
    $builder = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->addShipment(DocumentFixtures::shipment());

    expect(fn () => $builder->generate())->toThrow(InvalidArgumentException::class, 'client code');
});

test('generate rejects a labeled parcel without its existing label pdf', function (): void {
    $parcel = DocumentFixtures::parcel(['hasLabel' => true]);

    $builder = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment(DocumentFixtures::shipment(['parcels' => [$parcel]]));

    expect(fn () => $builder->generate())->toThrow(InvalidArgumentException::class, 'existingLabelPdf');
});
