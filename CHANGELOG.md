# Changelog

All notable changes to `brt-documento-di-ritiro` will be documented in this file.

## 0.0.1 - 2026-08-21

Initial release, extracted from the OLC application.

- Fluent `BrtDocumentoDiRitiro::document()` builder rendering the BRT documento di ritiro: corriere pages per delivery-address group and per-parcel segnacollo labels, returned as PDF binaries in a `GenerationResult`.
- Segnacollo barcode allocation from BRT number ranges via the `SegnacolloAllocator` contract, with a locking database implementation and check-digit generation.
- Fixed-width FNVAO booking record rendering via `BrtDocumentoDiRitiro::bookingFile()`.
- `Storage`-facade helpers on the result (`storeDocumentPdf`, `storeCorrierePdf`, `storeParcelLabel(s)`) that throw on failed writes.
- Branding (`BrandingData`) and the BRT client code are consumer-supplied inputs; the package bundles only BRT carrier assets.
- Label pages are embedded as alpha-free PNGs, sidestepping dompdf's per-pixel alpha decomposition (~4x faster page embedding).
