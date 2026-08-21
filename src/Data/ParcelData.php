<?php

namespace SmartDato\BrtDocumentoDiRitiro\Data;

use Spatie\LaravelData\Data;

class ParcelData extends Data
{
    public function __construct(
        public string $key,
        public float|int|null $weight = null,
        public float|int|null $volume = null,
        public ?string $existingBarcode = null,
        public bool $hasLabel = false,
        public ?string $existingLabelPdf = null,
    ) {}
}
