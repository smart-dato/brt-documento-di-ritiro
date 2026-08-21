<?php

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\StorageException;
use SmartDato\BrtDocumentoDiRitiro\Facades\BrtDocumentoDiRitiro;
use SmartDato\BrtDocumentoDiRitiro\GenerationResult;
use SmartDato\BrtDocumentoDiRitiro\Tests\Fixtures\DocumentFixtures;

function generateResult(int $parcelCount = 1): array
{
    DocumentFixtures::seedRange();

    $shipment = DocumentFixtures::shipment(parcelCount: $parcelCount);

    $result = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment($shipment)
        ->generate();

    return [$result, $shipment];
}

test('store helpers write to the default disk and are chainable', function (): void {
    Storage::fake();

    [$result, $shipment] = generateResult();
    $parcelKey = $shipment->parcels[0]->key;

    $returned = $result
        ->storeDocumentPdf('documents/document.pdf')
        ->storeCorrierePdf('documents/corriere.pdf')
        ->storeParcelLabel($parcelKey, 'labels/label.pdf');

    expect($returned)->toBeInstanceOf(GenerationResult::class);

    Storage::assertExists('documents/document.pdf');
    Storage::assertExists('documents/corriere.pdf');
    Storage::assertExists('labels/label.pdf');

    expect(Storage::get('documents/document.pdf'))->toBe($result->documentPdf())
        ->and(Storage::get('documents/corriere.pdf'))->toBe($result->corrierePdf())
        ->and(Storage::get('labels/label.pdf'))->toBe($result->parcelLabels()[$parcelKey]);
});

test('store helpers write to a named disk', function (): void {
    Storage::fake('s3');

    [$result] = generateResult();

    $result->storeDocumentPdf('documents/document.pdf', disk: 's3');

    Storage::disk('s3')->assertExists('documents/document.pdf');
});

test('storeParcelLabels resolves a path per parcel key', function (): void {
    Storage::fake();

    [$result] = generateResult(parcelCount: 3);

    $result->storeParcelLabels(fn (string $parcelKey): string => "labels/{$parcelKey}.pdf");

    foreach (array_keys($result->parcelLabels()) as $parcelKey) {
        Storage::assertExists("labels/{$parcelKey}.pdf");
    }
});

test('storeParcelLabel rejects a parcel without a rendered label', function (): void {
    Storage::fake();

    [$result] = generateResult();

    expect(fn () => $result->storeParcelLabel('unknown', 'labels/label.pdf'))
        ->toThrow(InvalidArgumentException::class, 'unknown');
});

test('store helpers throw when the disk write fails', function (): void {
    [$result] = generateResult();

    $failingDisk = Mockery::mock(Filesystem::class);
    $failingDisk->shouldReceive('put')->andReturnFalse();
    Storage::shouldReceive('disk')->with('s3')->andReturn($failingDisk);

    expect(fn () => $result->storeDocumentPdf('documents/document.pdf', disk: 's3'))
        ->toThrow(StorageException::class, 'Could not write "documents/document.pdf" to the s3 disk.');
});
