<?php

namespace SmartDato\BrtDocumentoDiRitiro;

final class FnvaoFile
{
    public function __construct(
        public readonly string $content,
        public readonly string $filename,
    ) {}
}
