<?php

namespace SmartDato\BrtDocumentoDiRitiro;

use SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator;
use SmartDato\BrtDocumentoDiRitiro\Data\BookingData;
use SmartDato\BrtDocumentoDiRitiro\Generator\FnvaoFileGenerator;

class BrtDocumentoDiRitiro
{
    public function __construct(protected SegnacolloAllocator $allocator) {}

    public function document(): DocumentBuilder
    {
        return new DocumentBuilder($this->allocator);
    }

    public function bookingFile(BookingData $booking): FnvaoFile
    {
        return (new FnvaoFileGenerator)->generate($booking);
    }

    public function allocator(): SegnacolloAllocator
    {
        return $this->allocator;
    }
}
