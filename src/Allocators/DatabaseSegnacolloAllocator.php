<?php

namespace SmartDato\BrtDocumentoDiRitiro\Allocators;

use Illuminate\Support\Facades\DB;
use SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator;
use SmartDato\BrtDocumentoDiRitiro\Models\SegnacolloRange;

class DatabaseSegnacolloAllocator implements SegnacolloAllocator
{
    public function next(): ?string
    {
        $connection = config('brt-documento-di-ritiro.allocator.connection');

        return DB::connection($connection)->transaction(function (): ?string {
            $range = SegnacolloRange::query()
                ->where(fn ($query) => $query->whereNull('next')->orWhereColumn('next', '!=', 'to'))
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($range === null) {
                return null;
            }

            $key = $this->incrementKey($range);
            if ($key === null) {
                return null;
            }

            $range->next = $key;
            $range->save();

            return $key;
        }, attempts: 15);
    }

    public function available(): int
    {
        $available = 0;

        foreach (SegnacolloRange::all() as $range) {
            if ($range->to == $range->next) {
                continue;
            }

            $from = (int) substr($range->from, 2, strlen($range->from) - 3);
            $to = (int) substr($range->to, 2, strlen($range->to) - 3);
            if (! is_null($range->next)) {
                $from = (int) substr($range->next, 2, strlen($range->next) - 3);
            }

            $available += $to - $from;
        }

        return $available;
    }

    private function incrementKey(SegnacolloRange $range): ?string
    {
        if (is_null($range->next)) {
            return $range->from;
        }

        if ($range->next == $range->to) {
            return null;
        }

        $prefix = substr($range->next, 0, 2);
        $value = (int) substr($range->next, 2, -1) + 1;
        $main = str_pad((string) $value, 11, '0', STR_PAD_LEFT);

        return $prefix.$main.self::calcCheckDigit($main);
    }

    public static function calcCheckDigit(string $data): int
    {
        $sumEven = 0;
        $sumOdd = 0;

        for ($i = strlen($data) - 1; $i >= 0; $i--) {
            if ($i % 2 == 0) {
                $sumEven += (int) $data[$i] * 3;
            } else {
                $sumOdd += (int) $data[$i];
            }
        }

        $remainder = ($sumEven + $sumOdd) % 10;

        return $remainder == 0 ? 0 : 10 - $remainder;
    }
}
