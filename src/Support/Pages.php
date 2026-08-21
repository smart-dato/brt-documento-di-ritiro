<?php

namespace SmartDato\BrtDocumentoDiRitiro\Support;

class Pages
{
    public function __construct(private string $range) {}

    public function __toString(): string
    {
        return $this->range;
    }
}
