<?php

namespace SmartDato\BrtDocumentoDiRitiro\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SmartDato\BrtDocumentoDiRitiro\DocumentBuilder document()
 * @method static \SmartDato\BrtDocumentoDiRitiro\FnvaoFile bookingFile(\SmartDato\BrtDocumentoDiRitiro\Data\BookingData $booking)
 * @method static \SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator allocator()
 *
 * @see \SmartDato\BrtDocumentoDiRitiro\BrtDocumentoDiRitiro
 */
class BrtDocumentoDiRitiro extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SmartDato\BrtDocumentoDiRitiro\BrtDocumentoDiRitiro::class;
    }
}
