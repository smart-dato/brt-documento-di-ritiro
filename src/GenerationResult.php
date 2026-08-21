<?php

namespace SmartDato\BrtDocumentoDiRitiro;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use SmartDato\BrtDocumentoDiRitiro\Data\BarcodeAllocation;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\StorageException;

final class GenerationResult
{
    /**
     * @param  array<string, string>  $parcelLabels  parcel key => PDF binary, newly rendered labels only
     * @param  array<string, BarcodeAllocation>  $allocations  parcel key => allocation, parcels without an existing barcode only
     * @param  array<string, string>  $barcodes  parcel key => barcode, for all parcels
     * @param  list<string>  $labeledParcelKeys  parcel keys whose label was rendered in this run
     * @param  array<string, string>  $segnacolloPngs  parcel key => PNG binary
     */
    public function __construct(
        private readonly string $documentPdf,
        private readonly string $corrierePdf,
        private readonly array $parcelLabels,
        private readonly array $allocations,
        private readonly array $barcodes,
        private readonly array $labeledParcelKeys,
        private readonly array $segnacolloPngs,
    ) {}

    public function documentPdf(): string
    {
        return $this->documentPdf;
    }

    public function corrierePdf(): string
    {
        return $this->corrierePdf;
    }

    /** @return array<string, string> */
    public function parcelLabels(): array
    {
        return $this->parcelLabels;
    }

    /** @return array<string, BarcodeAllocation> */
    public function allocatedBarcodes(): array
    {
        return $this->allocations;
    }

    /** @return array<string, string> */
    public function barcodes(): array
    {
        return $this->barcodes;
    }

    /** @return list<string> */
    public function labeledParcelKeys(): array
    {
        return $this->labeledParcelKeys;
    }

    /** @return array<string, string> */
    public function segnacolloPngs(): array
    {
        return $this->segnacolloPngs;
    }

    /**
     * @throws StorageException
     */
    public function storeDocumentPdf(string $path, ?string $disk = null): self
    {
        return $this->storeOnDisk($disk, $path, $this->documentPdf);
    }

    /**
     * @throws StorageException
     */
    public function storeCorrierePdf(string $path, ?string $disk = null): self
    {
        return $this->storeOnDisk($disk, $path, $this->corrierePdf);
    }

    /**
     * @throws StorageException
     */
    public function storeParcelLabel(string $parcelKey, string $path, ?string $disk = null): self
    {
        if (! isset($this->parcelLabels[$parcelKey])) {
            throw new InvalidArgumentException("No label was rendered for parcel \"{$parcelKey}\".");
        }

        return $this->storeOnDisk($disk, $path, $this->parcelLabels[$parcelKey]);
    }

    /**
     * Store every rendered parcel label; the callback resolves the path per parcel key.
     *
     * @param  callable(string $parcelKey): string  $path
     *
     * @throws StorageException
     */
    public function storeParcelLabels(callable $path, ?string $disk = null): self
    {
        foreach ($this->parcelLabels as $parcelKey => $labelPdf) {
            $this->storeOnDisk($disk, $path($parcelKey), $labelPdf);
        }

        return $this;
    }

    /**
     * @throws StorageException
     */
    private function storeOnDisk(?string $disk, string $path, string $contents): self
    {
        if (! Storage::disk($disk)->put($path, $contents)) {
            throw StorageException::writeFailed($path, $disk);
        }

        return $this;
    }
}
