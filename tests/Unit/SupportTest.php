<?php

use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Support\Assets;
use SmartDato\BrtDocumentoDiRitiro\Support\Numbers;
use SmartDato\BrtDocumentoDiRitiro\Support\Text;
use SmartDato\BrtDocumentoDiRitiro\Tests\Fixtures\DocumentFixtures;

test('addSpaceBeforeLastChar adds a space before the last character', function (): void {
    expect(Text::addSpaceBeforeLastChar('12345'))->toBe('1234 5')
        ->and(Text::addSpaceBeforeLastChar('AB'))->toBe('A B')
        ->and(Text::addSpaceBeforeLastChar('X'))->toBe(' X');
});

test('cleanAscii transliterates accented characters', function (): void {
    expect(Text::cleanAscii('Ä'))->toBe('Ae')
        ->and(Text::cleanAscii('über'))->toBe('ueber')
        ->and(Text::cleanAscii('Città'))->toBe('Citta')
        ->and(Text::cleanAscii(null))->toBe('');
});

test('numbers format uses two decimals with comma separator', function (): void {
    expect(Numbers::format(5))->toBe('5,00')
        ->and(Numbers::format(3.456))->toBe('3,46')
        ->and(Numbers::format(null))->toBe('0,00');
});

test('assets resolve to bundled files by default', function (): void {
    expect(Assets::font('verdana_bold'))->toBeFile()
        ->and(Assets::font('franklin_gothic'))->toBeFile()
        ->and(Assets::image('brt_logo'))->toBeFile()
        ->and(Assets::image('brt_logo_text'))->toBeFile()
        ->and(Assets::image('brt_barcode_background'))->toBeFile();
});

test('assets prefer configured overrides', function (): void {
    config()->set('brt-documento-di-ritiro.fonts.verdana_bold', '/custom/font.ttf');

    expect(Assets::font('verdana_bold'))->toBe('/custom/font.ttf');
});

test('address equality compares the documento di ritiro fields', function (): void {
    $address = DocumentFixtures::deliveryAddress();

    expect($address->equals(DocumentFixtures::deliveryAddress()))->toBeTrue()
        ->and($address->equals(DocumentFixtures::deliveryAddress(['street' => 'Other 2'])))->toBeFalse()
        ->and($address->equals(DocumentFixtures::deliveryAddress(['email' => 'other@example.com'])))->toBeFalse()
        ->and($address->equals(DocumentFixtures::deliveryAddress(['phone' => '+39 000'])))->toBeFalse();
});

test('address name helpers fall back between person and company', function (): void {
    $both = new AddressData(personName: 'Person', companyName: 'Company');
    $personOnly = new AddressData(personName: 'Person');
    $companyOnly = new AddressData(companyName: 'Company');

    expect($both->personOrCompanyName())->toBe('Person')
        ->and($both->companyOrPersonName())->toBe('Company')
        ->and($personOnly->companyOrPersonName())->toBe('Person')
        ->and($companyOnly->personOrCompanyName())->toBe('Company');
});
