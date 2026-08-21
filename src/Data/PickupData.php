<?php

namespace SmartDato\BrtDocumentoDiRitiro\Data;

use Spatie\LaravelData\Data;

class PickupData extends Data
{
    public function __construct(
        public string $documentNumber,
        public ?string $documentDate = null,
        public ?string $comment = null,
    ) {}
}
