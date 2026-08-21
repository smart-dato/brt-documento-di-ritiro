<?php

namespace SmartDato\BrtDocumentoDiRitiro\Exceptions;

use Exception;

class StorageException extends Exception
{
    public static function writeFailed(string $path, ?string $disk): self
    {
        $diskName = $disk ?? 'default';

        return new self("Could not write \"{$path}\" to the {$diskName} disk.");
    }
}
