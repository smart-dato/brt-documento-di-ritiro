<?php

namespace SmartDato\BrtDocumentoDiRitiro\Support;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use SmartDato\BrtDocumentoDiRitiro\Exceptions\PdfMergeException;

class PdfMerger
{
    private Fpdi $fpdi;

    /** @var list<array{source: StreamReader|string, pages: Pages|string|null}> */
    private array $sources = [];

    public function __construct()
    {
        $this->fpdi = new Fpdi;
    }

    public function addRaw(string $content, Pages|string|null $pages = null): static
    {
        $this->sources[] = [
            'source' => StreamReader::createByString($content),
            'pages' => $pages,
        ];

        return $this;
    }

    public function addFile(string $filename, Pages|string|null $pages = null): static
    {
        $this->sources[] = [
            'source' => $filename,
            'pages' => $pages,
        ];

        return $this;
    }

    /**
     * @throws PdfMergeException
     */
    public function merge(): string
    {
        try {
            foreach ($this->sources as $source) {
                $this->processSource($source);
            }

            return $this->fpdi->Output('S');
        } catch (\Exception $e) {
            throw new PdfMergeException($e->getMessage());
        }
    }

    /**
     * @param  array{source: StreamReader|string, pages: Pages|string|null}  $source
     */
    private function processSource(array $source): void
    {
        $pageCount = $this->fpdi->setSourceFile($source['source']);

        $pages = $source['pages'] === null
            ? range(1, $pageCount)
            : $this->parsePageRange($source['pages']);

        foreach ($pages as $pageNumber) {
            if ($pageNumber > $pageCount) {
                continue;
            }

            $template = $this->fpdi->importPage($pageNumber);
            $size = $this->fpdi->getTemplateSize($template);

            $this->fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $this->fpdi->useTemplate($template);
        }
    }

    /**
     * @return list<int>
     */
    private function parsePageRange(Pages|string $rangeStr): array
    {
        if ($rangeStr instanceof Pages) {
            $rangeStr = (string) $rangeStr;
        }

        $pages = [];
        foreach (explode(',', $rangeStr) as $part) {
            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part);
                for ($i = (int) $start; $i <= (int) $end; $i++) {
                    $pages[] = $i;
                }

                continue;
            }

            $pages[] = (int) $part;
        }

        return $pages;
    }
}
