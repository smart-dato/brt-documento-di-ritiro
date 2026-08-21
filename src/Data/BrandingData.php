<?php

namespace SmartDato\BrtDocumentoDiRitiro\Data;

use Spatie\LaravelData\Data;

class BrandingData extends Data
{
    /**
     * @param  string  $termsUrl  rendered in the signature line ("FIRMA MITTENTE PER ACCETTAZIONE ... SU {termsUrl}")
     * @param  ?string  $sideLogoPath  image file inserted on the side panel; omitted when null
     * @param  ?string  $fallbackRecipientName  person name printed on the substituted delivery address for non-IT destinations
     */
    public function __construct(
        public string $termsUrl,
        public ?string $sideLogoPath = null,
        public ?string $fallbackRecipientName = null,
    ) {}
}
