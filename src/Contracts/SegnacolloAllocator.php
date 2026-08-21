<?php

namespace SmartDato\BrtDocumentoDiRitiro\Contracts;

interface SegnacolloAllocator
{
    /**
     * Allocate the next segnacollo barcode, or return null when all ranges are exhausted.
     */
    public function next(): ?string;

    /**
     * Count the barcodes still available across all ranges.
     */
    public function available(): int;
}
