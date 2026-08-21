<?php

namespace SmartDato\BrtDocumentoDiRitiro\Data;

use Spatie\LaravelData\Data;

class BarcodeAllocation extends Data
{
    public function __construct(
        public string $parcelKey,
        public string $barcode,
        public bool $newlyAllocated,
        public bool $rangeExhausted,
    ) {}
}
