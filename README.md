# BRT Documento di Ritiro

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smart-dato/brt-documento-di-ritiro.svg?style=flat-square)](https://packagist.org/packages/smart-dato/brt-documento-di-ritiro)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/smart-dato/brt-documento-di-ritiro/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/smart-dato/brt-documento-di-ritiro/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/smart-dato/brt-documento-di-ritiro.svg?style=flat-square)](https://packagist.org/packages/smart-dato/brt-documento-di-ritiro)

Generate **BRT (Bartolini) "Documento di Ritiro"** pickup paperwork from plain PHP data objects:

- the A4 **corriere document** ("DA CONSEGNARE AL CORRIERE") — one page per delivery-address group, with segnacollo thumbnails, weights, volumes and cash-on-delivery totals,
- one A4 **parcel label** per parcel ("DA APPLICARE SUL PACCO"), each carrying its segnacollo barcode and the shipment barcode strip,
- **segnacollo barcode allocation** from BRT number ranges, with database locking against duplicate numbers,
- the fixed-width **FNVAO booking record** BRT's VAS system parses.

Everything is rendered in memory and returned as binaries — the package never touches your models, storage paths or database rows (except the barcode-range table it owns). Optional helpers store the results on any Laravel filesystem disk.

## Requirements

- PHP 8.4+ with the GD extension
- Laravel 11, 12 or 13

## Installation

```bash
composer require smart-dato/brt-documento-di-ritiro
```

Publish the config file if you need to override defaults:

```bash
php artisan vendor:publish --tag="brt-documento-di-ritiro-config"
```

The segnacollo allocator persists its number ranges in a table (default `brt_documento_di_ritiro`). Publish and run the migration unless the table already exists in your schema:

```bash
php artisan vendor:publish --tag="brt-documento-di-ritiro-migrations"
php artisan migrate
```

## Usage

### Generating a documento di ritiro

Describe the pickup with data objects and let the fluent builder render everything:

```php
use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Data\BrandingData;
use SmartDato\BrtDocumentoDiRitiro\Data\ParcelData;
use SmartDato\BrtDocumentoDiRitiro\Data\PickupData;
use SmartDato\BrtDocumentoDiRitiro\Data\ShipmentData;
use SmartDato\BrtDocumentoDiRitiro\Facades\BrtDocumentoDiRitiro;

$result = BrtDocumentoDiRitiro::document()
    ->pickup(new PickupData(
        documentNumber: 'OLP202600044200',
        documentDate: '2026-08-24',
    ))
    ->sender(new AddressData(
        personName: 'Mario Balistreri',
        street: 'Via Venezia 22',
        zipcode: '94100',
        city: 'Enna',
        countryCode: 'it',
        countryName: 'Italy',
        phone: '3476221921',
    ))
    ->branding(new BrandingData(
        termsUrl: 'www.example.com',
        sideLogoPath: '/path/to/your/side-logo.png',
    ))
    ->clientCode('0123456')
    ->addShipment(new ShipmentData(
        key: 'SHIP0001',
        deliveryAddress: new AddressData(/* ... */),
        onBehalfOf: 'Your Company',
        declaredParcelCount: 2,
        parcels: [
            new ParcelData(key: 'SHIP0001X1', weight: 10.0, volume: 0.04),
            new ParcelData(key: 'SHIP0001X2', weight: 10.0, volume: 0.07),
        ],
        useBarcodeSideLayout: true,
    ))
    ->generate();
```

`branding()` and `clientCode()` are required: the BRT client code, terms URL, side logo and non-IT fallback recipient are your business data — the package ships none of it.

The `GenerationResult` carries every artifact and the barcode bookkeeping:

```php
$result->documentPdf();       // corriere pages + all parcel labels, merged
$result->corrierePdf();       // corriere pages only
$result->parcelLabels();      // parcel key => label PDF (newly rendered labels)
$result->barcodes();          // parcel key => segnacollo barcode (all parcels)
$result->allocatedBarcodes(); // parcel key => BarcodeAllocation (newly allocated only)
$result->labeledParcelKeys(); // parcels that received a label in this run
```

Store the artifacts on any Laravel disk — the helpers throw a `StorageException` when a write fails:

```php
$result->storeCorrierePdf('pickups/corriere.pdf')
    ->storeDocumentPdf('pickups/document.pdf')
    ->storeDocumentPdf('pickups/document.pdf', disk: 's3')
    ->storeParcelLabels(fn (string $parcelKey) => "labels/{$parcelKey}.pdf");
```

Parcels that already carry a barcode keep it (`existingBarcode`), and parcels that already have a label are skipped but stay part of the merged document (`hasLabel` + `existingLabelPdf`).

### Segnacollo barcode allocation

Barcodes are drawn from number ranges BRT assigns to you. Seed them once:

```php
use SmartDato\BrtDocumentoDiRitiro\Models\SegnacolloRange;

SegnacolloRange::create([
    'from' => '10000000000010',
    'to' => '10000001000002',
    'next' => null,
]);
```

The database allocator hands out sequential numbers with the BRT check digit inside a locking transaction, rolls over between ranges and returns `null` on exhaustion (the generator then falls back to the parcel key). Monitor the remaining stock:

```php
use SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator;

app(SegnacolloAllocator::class)->available();
```

Bind your own `SegnacolloAllocator` implementation to replace the database-backed default.

### FNVAO booking record

Render the fixed-width booking file BRT expects when a pickup is announced:

```php
use SmartDato\BrtDocumentoDiRitiro\Data\BookingData;
use SmartDato\BrtDocumentoDiRitiro\Facades\BrtDocumentoDiRitiro;

$file = BrtDocumentoDiRitiro::bookingFile(new BookingData(
    reference: 'OLP202600044200',
    referenceLength: 15,
    clientCode: '0123456',
    exportedAt: '2026-08-21 14:30:15',
    pickupAfter: '2026-08-24 09:30:00',
    sender: new AddressData(/* ... incl. province */),
    senderStreetLines: ['Via Venezia 22'],
    weight: 21.5,
    volume: 0.113,
    parcelCount: 2,
    consignee: null, // hub or direct-delivery consignee block, blank when null
));

$file->content;  // the FNVAO record (ASCII, fixed-width)
$file->filename; // fnvao_20260821_143015#####.txt
```

## Configuration

```php
return [
    'max_parcels' => 20,                  // BRT's per-document parcel cap
    'allocator' => [
        'connection' => null,             // connection for the range table
        'table' => 'brt_documento_di_ritiro',
    ],
    'fallback_delivery_address' => [...], // printed for non-IT destinations
    'fonts' => [...],                     // per-font file overrides (null = bundled)
    'images' => [...],                    // BRT logo/background overrides (null = bundled)
];
```

## A note on the bundled fonts

The rendered form uses Verdana and Franklin Gothic, which are **proprietary typefaces**. The bundled copies exist so the output matches the documents BRT has historically accepted — make sure your own licensing situation covers their use, or point the `fonts` config entries at typefaces you are licensed for.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [SmartDato](https://github.com/smart-dato)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
