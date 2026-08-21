<?php

namespace SmartDato\BrtDocumentoDiRitiro\Rendering;

use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use SmartDato\BrtDocumentoDiRitiro\Support\Assets;
use SmartDato\BrtDocumentoDiRitiro\Support\BarcodeImage;
use SmartDato\BrtDocumentoDiRitiro\Support\Text;

class SegnacolloRenderer
{
    public function render(string $barcode, float|int $factor = 70): Image
    {
        $img = ImageManagerStatic::canvas((int) (21.01 * $factor), (int) (29.7 * $factor), '#FFF');
        $x_start = 3.67;
        $y_start = 2.53;
        $verdanaBold = Assets::font('verdana_bold');

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

        $img->crop(430, 180, 885, 470);

        return $img;
    }
}
