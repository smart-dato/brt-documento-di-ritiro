<?php

namespace SmartDato\BrtDocumentoDiRitiro\Rendering;

use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManagerStatic;
use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Data\BrandingData;
use SmartDato\BrtDocumentoDiRitiro\Data\PickupData;
use SmartDato\BrtDocumentoDiRitiro\Support\Assets;
use SmartDato\BrtDocumentoDiRitiro\Support\BorderoGrouper;
use SmartDato\BrtDocumentoDiRitiro\Support\Png;

class CorrierePageRenderer
{
    public function __construct(
        private readonly FormLayoutRenderer $layoutRenderer = new FormLayoutRenderer,
    ) {}

    /**
     * Render the "DA CONSEGNARE AL CORRIERE" pages (one per delivery-address group) as a single PDF.
     *
     * @param  array<string, string>  $segnacolloPngs  parcel key => PNG binary
     */
    public function render(
        PickupData $pickup,
        AddressData $sender,
        BorderoGrouper $grouper,
        BrandingData $branding,
        string $clientCode,
        array $segnacolloPngs,
        float|int $factor = 70,
    ): string {
        $sideText = 'DA CONSEGNARE AL CORRIERE';
        $labelData = [];

        foreach ($grouper->groups() as $groupKey => $entries) {
            $shipment = $grouper->shipmentFor($groupKey);
            $parcelCount = count($entries);
            $measurement = $grouper->measurementFor($groupKey);

            $context = new RenderContext(
                pickup: $pickup,
                sender: $sender,
                shipment: $shipment,
                parcel: null,
                branding: $branding,
                clientCode: $clientCode,
                sideText: $sideText,
                measurement: $measurement,
                pageCounter: $parcelCount,
                factor: $factor,
            );

            $img = $this->layoutRenderer->render($context, $grouper);

            $verdanaMono = Assets::font('verdana_mono');
            $responseSize = 0.3;
            $img->text('DA', 6.75 * $factor, 23 * $factor, function ($font) use ($verdanaMono, $responseSize, $factor): void {
                $font->file($verdanaMono);
                $font->size($responseSize * $factor);
                $font->color('#000');
                $font->align('left');
                $font->valign('bottom');
            });
            if (array_key_exists(0, $entries)) {
                $segnacollo = ImageManagerStatic::make($segnacolloPngs[$entries[0]['parcel']->key]);
                $img->insert($segnacollo, 'top-right', (int) (10.5 * $factor), (int) (23.0 * $factor));
            }

            $img->text('A', 14.75 * $factor, 23 * $factor, function ($font) use ($verdanaMono, $responseSize, $factor): void {
                $font->file($verdanaMono);
                $font->size($responseSize * $factor);
                $font->color('#000');
                $font->align('left');
                $font->valign('bottom');
            });
            if (array_key_exists($parcelCount - 1, $entries)) {
                $segnacollo = ImageManagerStatic::make($segnacolloPngs[$entries[$parcelCount - 1]['parcel']->key]);
                $img->insert($segnacollo, 'top-right', (int) (3.5 * $factor), (int) (23.0 * $factor));
            }

            $img->crop(1295, 1820, 27, 173);
            $labelData[] = Png::dataUrl($img);
        }

        $html = view('brt-documento-di-ritiro::segnacolli', ['labelData' => $labelData])->render();

        return Pdf::loadHTML($html)->output();
    }
}
