<?php

namespace SmartDato\BrtDocumentoDiRitiro\Support;

use Intervention\Image\Image;

class Png
{
    /**
     * Encode a fully opaque canvas as an alpha-free PNG data-url. Dompdf's PDF
     * writer decomposes an RGBA alpha channel pixel by pixel in PHP (~290ms per
     * A4 page); dropping the unused channel makes the same page embed in ~70ms.
     */
    public static function dataUrl(Image $image): string
    {
        $core = $image->getCore();
        imagesavealpha($core, false);

        ob_start();
        imagepng($core);
        $binary = ob_get_clean();

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
