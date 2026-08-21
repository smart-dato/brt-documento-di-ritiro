<?php

namespace SmartDato\BrtDocumentoDiRitiro\Rendering;

use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManagerStatic;
use SmartDato\BrtDocumentoDiRitiro\Support\Assets;
use SmartDato\BrtDocumentoDiRitiro\Support\BarcodeImage;
use SmartDato\BrtDocumentoDiRitiro\Support\BorderoGrouper;
use SmartDato\BrtDocumentoDiRitiro\Support\Png;
use SmartDato\BrtDocumentoDiRitiro\Support\Text;

class ColloLabelRenderer
{
    public function __construct(
        private readonly FormLayoutRenderer $layoutRenderer = new FormLayoutRenderer,
    ) {}

    /**
     * Render one "DA APPLICARE SUL PACCO" parcel label as a PDF.
     */
    public function render(RenderContext $context, BorderoGrouper $grouper, string $barcode): string
    {
        $factor = $context->factor;
        $x_start = 3.67;
        $y_start = 2.53;
        $verdanaBold = Assets::font('verdana_bold');

        $img = $this->layoutRenderer->render($context, $grouper);

        $brtBarcodeBackground = ImageManagerStatic::make(Assets::image('brt_barcode_background'));
        $backgroundResize = 2.5;
        $brtBarcodeBackground->resize((15.1 * $factor) / $backgroundResize, (6 * $factor) / $backgroundResize);

        $img->insert($brtBarcodeBackground, 'top-right', (int) (2.3 * $factor), (int) (6.8 * $factor));

        $keyWithSpace = Text::addSpaceBeforeLastChar($barcode);
        $barcodeResize = 3.5;
        $barcodeImg = ImageManagerStatic::make(BarcodeImage::resource($barcode, 0.1 * $factor));
        $barcodeImg->resize((15.05 * $factor) / $barcodeResize, (5 * $factor) / $barcodeResize);
        $img->insert($barcodeImg, 'top-right', (int) (2.8 * $factor), (int) (7 * $factor));

        $img->text($keyWithSpace, ($x_start + 12.2) * $factor, ($y_start + 6.2) * $factor, function ($font) use ($verdanaBold, $factor): void {
            $font->file($verdanaBold);
            $font->size(0.25 * $factor);
            $font->color('#000');
            $font->align('center');
            $font->valign('bottom');
        });

        $img->crop(1295, 1820, 27, 173);

        $shipmentBarcode = ImageManagerStatic::canvas((int) (21.01 * $factor), (int) (29.7 * $factor), '#FFF');
        $barcodeRef = ImageManagerStatic::make(BarcodeImage::resource($context->shipment->key, 0.1 * $factor));
        $barcodeRef->resize(15.05 * $factor, 5 * $factor);
        $shipmentBarcode->insert($barcodeRef, 'top-left', (int) (0.9 * $factor), (int) (4 * $factor));

        $html = view('brt-documento-di-ritiro::parcel-label', [
            'labelData1' => Png::dataUrl($img),
            'labelData2' => null,
            'barcode' => Png::dataUrl($shipmentBarcode),
        ])->render();

        return Pdf::loadHTML($html)->output();
    }
}
