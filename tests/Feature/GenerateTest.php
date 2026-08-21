<?php

use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\TooManyParcelsException;
use SmartDato\BrtDocumentoDiRitiro\Facades\BrtDocumentoDiRitiro;
use SmartDato\BrtDocumentoDiRitiro\Tests\Fixtures\DocumentFixtures;

function pdfPageCount(string $pdf): int
{
    return (new Fpdi)->setSourceFile(StreamReader::createByString($pdf));
}

test('generate throws when the document has more than 20 parcels', function (): void {
    DocumentFixtures::seedRange();

    $builder = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment(DocumentFixtures::shipment(parcelCount: 21));

    expect(fn () => $builder->generate())
        ->toThrow(TooManyParcelsException::class, 'BRT: too many parcels, max 20');
});

test('generate renders the document for a single parcel', function (): void {
    DocumentFixtures::seedRange();

    $shipment = DocumentFixtures::shipment(parcelCount: 1);

    $result = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment($shipment)
        ->generate();

    $parcelKey = $shipment->parcels[0]->key;

    expect($result->documentPdf())->toStartWith('%PDF')
        ->and($result->corrierePdf())->toStartWith('%PDF')
        ->and($result->parcelLabels())->toHaveKey($parcelKey)
        ->and($result->parcelLabels()[$parcelKey])->toStartWith('%PDF')
        ->and($result->labeledParcelKeys())->toBe([$parcelKey])
        ->and($result->barcodes())->toHaveKey($parcelKey)
        ->and($result->allocatedBarcodes()[$parcelKey]->newlyAllocated)->toBeTrue()
        ->and($result->allocatedBarcodes()[$parcelKey]->barcode)->toBe('10000000000010')
        ->and($result->segnacolloPngs()[$parcelKey])->toStartWith("\x89PNG");

    expect(pdfPageCount($result->documentPdf()))
        ->toBe(pdfPageCount($result->corrierePdf()) + 1);
});

test('generate renders one label per parcel and merges them all', function (): void {
    DocumentFixtures::seedRange();

    $shipment = DocumentFixtures::shipment(['useBarcodeSideLayout' => true], parcelCount: 3);

    $result = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment($shipment)
        ->generate();

    expect($result->parcelLabels())->toHaveCount(3)
        ->and($result->labeledParcelKeys())->toHaveCount(3)
        ->and(array_unique($result->barcodes()))->toHaveCount(3);

    $labelPageCount = array_sum(array_map(pdfPageCount(...), $result->parcelLabels()));

    expect(pdfPageCount($result->documentPdf()))
        ->toBe(pdfPageCount($result->corrierePdf()) + $labelPageCount);
});

test('generate reuses an existing barcode without allocating', function (): void {
    DocumentFixtures::seedRange();

    $parcel = DocumentFixtures::parcel(['existingBarcode' => '10000000001234']);
    $shipment = DocumentFixtures::shipment(['parcels' => [$parcel]]);

    $result = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment($shipment)
        ->generate();

    expect($result->barcodes()[$parcel->key])->toBe('10000000001234')
        ->and($result->allocatedBarcodes())->toBe([]);
});

test('generate skips parcels that already have a label but keeps their page in the merged document', function (): void {
    DocumentFixtures::seedRange();

    $existingLabelPdf = Pdf::loadHTML('<p>existing label</p>')->output();

    $labeled = DocumentFixtures::parcel([
        'existingBarcode' => '10000000001234',
        'hasLabel' => true,
        'existingLabelPdf' => $existingLabelPdf,
    ]);
    $unlabeled = DocumentFixtures::parcel();
    $shipment = DocumentFixtures::shipment(['parcels' => [$labeled, $unlabeled]]);

    $result = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment($shipment)
        ->generate();

    expect($result->parcelLabels())->toHaveKey($unlabeled->key)
        ->and($result->parcelLabels())->not->toHaveKey($labeled->key)
        ->and($result->labeledParcelKeys())->toBe([$unlabeled->key]);

    expect(pdfPageCount($result->documentPdf()))->toBe(
        pdfPageCount($result->corrierePdf())
        + pdfPageCount($existingLabelPdf)
        + pdfPageCount($result->parcelLabels()[$unlabeled->key])
    );
});

test('generate falls back to the parcel key when all ranges are exhausted', function (): void {
    $parcel = DocumentFixtures::parcel();
    $shipment = DocumentFixtures::shipment(['parcels' => [$parcel]]);

    $result = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment($shipment)
        ->generate();

    expect($result->barcodes()[$parcel->key])->toBe($parcel->key)
        ->and($result->allocatedBarcodes()[$parcel->key]->rangeExhausted)->toBeTrue()
        ->and($result->allocatedBarcodes()[$parcel->key]->newlyAllocated)->toBeFalse();
});

test('generate renders one corriere page per delivery address group', function (): void {
    DocumentFixtures::seedRange();

    $sameAddress = DocumentFixtures::deliveryAddress();
    $otherAddress = DocumentFixtures::deliveryAddress([
        'personName' => 'Person B',
        'companyName' => 'Company B',
        'street' => 'Via Milano 5',
        'zipcode' => '20100',
        'city' => 'MILANO',
    ]);

    $result = BrtDocumentoDiRitiro::document()
        ->pickup(DocumentFixtures::pickup())
        ->sender(DocumentFixtures::senderAddress())
        ->branding(DocumentFixtures::branding())
        ->clientCode('0481740')
        ->addShipment(DocumentFixtures::shipment(['deliveryAddress' => $sameAddress]))
        ->addShipment(DocumentFixtures::shipment(['deliveryAddress' => $sameAddress]))
        ->addShipment(DocumentFixtures::shipment(['deliveryAddress' => $otherAddress]))
        ->generate();

    expect(pdfPageCount($result->corrierePdf()))->toBe(2);
});
