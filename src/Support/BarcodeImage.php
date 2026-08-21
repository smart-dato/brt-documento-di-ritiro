<?php

namespace SmartDato\BrtDocumentoDiRitiro\Support;

use Laminas\Barcode\Barcode;

class BarcodeImage
{
    /**
     * @return resource|\GdImage
     */
    public static function resource(string $text, float|int $factor = 2)
    {
        $barcodeOptions = [
            'text' => $text,
            'withChecksum' => true,
            'factor' => $factor,
            'drawText' => false,
        ];

        $rendererOptions = ['imageType' => 'png'];

        return Barcode::factory('code128', 'image', $barcodeOptions, $rendererOptions)->draw();
    }
}
