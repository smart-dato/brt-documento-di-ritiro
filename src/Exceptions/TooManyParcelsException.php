<?php

namespace SmartDato\BrtDocumentoDiRitiro\Exceptions;

use Exception;

class TooManyParcelsException extends Exception
{
    public static function max(int $maxParcels): self
    {
        return new self("BRT: too many parcels, max {$maxParcels}");
    }
}
