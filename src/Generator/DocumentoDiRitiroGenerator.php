<?php

namespace SmartDato\BrtDocumentoDiRitiro\Generator;

use SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator;
use SmartDato\BrtDocumentoDiRitiro\Data\BarcodeAllocation;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\PdfMergeException;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\TooManyParcelsException;
use SmartDato\BrtDocumentoDiRitiro\GenerationResult;
use SmartDato\BrtDocumentoDiRitiro\Rendering\ColloLabelRenderer;
use SmartDato\BrtDocumentoDiRitiro\Rendering\CorrierePageRenderer;
use SmartDato\BrtDocumentoDiRitiro\Rendering\RenderContext;
use SmartDato\BrtDocumentoDiRitiro\Rendering\SegnacolloRenderer;
use SmartDato\BrtDocumentoDiRitiro\Support\BorderoGrouper;
use SmartDato\BrtDocumentoDiRitiro\Support\PdfMerger;

class DocumentoDiRitiroGenerator
{
    public function __construct(
        private readonly SegnacolloAllocator $allocator,
        private readonly SegnacolloRenderer $segnacolloRenderer = new SegnacolloRenderer,
        private readonly CorrierePageRenderer $corrierePageRenderer = new CorrierePageRenderer,
        private readonly ColloLabelRenderer $colloLabelRenderer = new ColloLabelRenderer,
    ) {}

    /**
     * @throws TooManyParcelsException
     * @throws PdfMergeException
     */
    public function generate(DocumentSpec $spec): GenerationResult
    {
        $this->guardParcelCount($spec);

        [$barcodes, $allocations] = $this->allocateBarcodes($spec);

        $grouper = new BorderoGrouper($spec->shipments);

        $segnacolloPngs = [];
        foreach ($grouper->groups() as $entries) {
            foreach ($entries as $entry) {
                $parcelKey = $entry['parcel']->key;
                $segnacolloPngs[$parcelKey] = (string) $this->segnacolloRenderer
                    ->render($barcodes[$parcelKey])
                    ->encode('png');
            }
        }

        $corrierePdf = $this->corrierePageRenderer->render(
            pickup: $spec->pickup,
            sender: $spec->sender,
            grouper: $grouper,
            branding: $spec->branding,
            clientCode: $spec->clientCode,
            segnacolloPngs: $segnacolloPngs,
        );

        $parcelLabels = [];
        $labeledParcelKeys = [];
        foreach ($spec->shipments as $shipment) {
            foreach ($shipment->parcels as $parcel) {
                if ($parcel->hasLabel) {
                    continue;
                }

                $context = new RenderContext(
                    pickup: $spec->pickup,
                    sender: $spec->sender,
                    shipment: $shipment,
                    parcel: $parcel,
                    branding: $spec->branding,
                    clientCode: $spec->clientCode,
                    sideText: 'DA APPLICARE SUL PACCO',
                    measurement: null,
                    pageCounter: $grouper->positionOf($parcel->key),
                    factor: 70,
                );

                $parcelLabels[$parcel->key] = $this->colloLabelRenderer->render($context, $grouper, $barcodes[$parcel->key]);
                $labeledParcelKeys[] = $parcel->key;
            }
        }

        $merger = new PdfMerger;
        $merger->addRaw($corrierePdf);
        foreach ($spec->shipments as $shipment) {
            foreach ($shipment->parcels as $parcel) {
                $merger->addRaw($parcelLabels[$parcel->key] ?? $parcel->existingLabelPdf);
            }
        }

        return new GenerationResult(
            documentPdf: $merger->merge(),
            corrierePdf: $corrierePdf,
            parcelLabels: $parcelLabels,
            allocations: $allocations,
            barcodes: $barcodes,
            labeledParcelKeys: $labeledParcelKeys,
            segnacolloPngs: $segnacolloPngs,
        );
    }

    /**
     * @throws TooManyParcelsException
     */
    private function guardParcelCount(DocumentSpec $spec): void
    {
        $maxParcels = (int) config('brt-documento-di-ritiro.max_parcels', 20);

        $parcelCount = 0;
        foreach ($spec->shipments as $shipment) {
            $parcelCount += count($shipment->parcels);
        }

        if ($parcelCount > $maxParcels) {
            throw TooManyParcelsException::max($maxParcels);
        }
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, BarcodeAllocation>}
     */
    private function allocateBarcodes(DocumentSpec $spec): array
    {
        $barcodes = [];
        $allocations = [];

        foreach ($spec->shipments as $shipment) {
            foreach ($shipment->parcels as $parcel) {
                if (($parcel->existingBarcode ?? '') !== '') {
                    $barcodes[$parcel->key] = $parcel->existingBarcode;

                    continue;
                }

                $allocated = $this->allocator->next();
                $barcode = $allocated ?? $parcel->key;

                $barcodes[$parcel->key] = $barcode;
                $allocations[$parcel->key] = new BarcodeAllocation(
                    parcelKey: $parcel->key,
                    barcode: $barcode,
                    newlyAllocated: $allocated !== null,
                    rangeExhausted: $allocated === null,
                );
            }
        }

        return [$barcodes, $allocations];
    }
}
