<?php

namespace SmartDato\BrtDocumentoDiRitiro\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $from
 * @property string $to
 * @property ?string $next
 */
class SegnacolloRange extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('brt-documento-di-ritiro.allocator.table', 'brt_documento_di_ritiro');
    }

    public function getConnectionName(): ?string
    {
        return config('brt-documento-di-ritiro.allocator.connection') ?? parent::getConnectionName();
    }
}
