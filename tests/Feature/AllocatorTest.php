<?php

use SmartDato\BrtDocumentoDiRitiro\Allocators\DatabaseSegnacolloAllocator;
use SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator;
use SmartDato\BrtDocumentoDiRitiro\Tests\Fixtures\DocumentFixtures;

test('next returns the range start on first allocation', function (): void {
    DocumentFixtures::seedRange();

    expect(app(SegnacolloAllocator::class)->next())->toBe('10000000000010');
});

test('next increments with the correct check digit', function (): void {
    DocumentFixtures::seedRange();

    $allocator = app(SegnacolloAllocator::class);

    expect($allocator->next())->toBe('10000000000010')
        ->and($allocator->next())->toBe('10000000000024')
        ->and($allocator->next())->toBe('10000000000031');
});

test('next hands out unique barcodes across many allocations', function (): void {
    DocumentFixtures::seedRange();

    $allocator = app(SegnacolloAllocator::class);

    $barcodes = [];
    for ($i = 0; $i < 50; $i++) {
        $barcodes[] = $allocator->next();
    }

    expect(array_unique($barcodes))->toHaveCount(50)
        ->and($barcodes)->not->toContain(null);
});

test('next rolls over to the following range when one is exhausted', function (): void {
    DocumentFixtures::seedRange(from: '10000000000010', to: '10000000000010');
    DocumentFixtures::seedRange(from: '20000000000015', to: '20000001000000');

    $allocator = app(SegnacolloAllocator::class);

    expect($allocator->next())->toBe('10000000000010')
        ->and($allocator->next())->toBe('20000000000015');
});

test('next returns null when every range is exhausted', function (): void {
    DocumentFixtures::seedRange(from: '10000000000010', to: '10000000000010');

    $allocator = app(SegnacolloAllocator::class);

    expect($allocator->next())->toBe('10000000000010')
        ->and($allocator->next())->toBeNull();
});

test('next returns null without any range', function (): void {
    expect(app(SegnacolloAllocator::class)->next())->toBeNull();
});

test('available counts the remaining barcodes across ranges', function (): void {
    DocumentFixtures::seedRange();

    $allocator = app(SegnacolloAllocator::class);

    expect($allocator->available())->toBe(99999);

    $allocator->next();
    $allocator->next();

    expect($allocator->available())->toBe(99998);
});

test('calcCheckDigit matches the historical algorithm', function (): void {
    expect(DatabaseSegnacolloAllocator::calcCheckDigit('00000000002'))->toBe(4)
        ->and(DatabaseSegnacolloAllocator::calcCheckDigit('00000000003'))->toBe(1)
        ->and(DatabaseSegnacolloAllocator::calcCheckDigit('00000000000'))->toBe(0);
});
