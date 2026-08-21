<?php

use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Data\BookingData;
use SmartDato\BrtDocumentoDiRitiro\Generator\FnvaoFileGenerator;

function makeBooking(array $overrides = []): BookingData
{
    return new BookingData(...array_merge([
        'reference' => 'OLP202600044200',
        'referenceLength' => 15,
        'clientCode' => '0482854',
        'exportedAt' => '2026-08-21 14:30:15',
        'pickupAfter' => '2026-08-24 09:30:00',
        'sender' => new AddressData(
            personName: 'Sender Person',
            companyName: 'OMEST SRL',
            street: 'Via L. Negrelli 15',
            zipcode: '39100',
            city: 'BOLZANO',
            countryCode: 'it',
            phone: '+39 0471 1962318',
            province: 'BZ',
        ),
        'senderStreetLines' => ['Via L. Negrelli 15'],
        'weight' => 21.5,
        'volume' => 0.113,
        'parcelCount' => 2,
    ], $overrides));
}

test('the record has the fixed layout', function (): void {
    $content = (new FnvaoFileGenerator)->generate(makeBooking())->content;

    expect($content)->toEndWith("\r\n")
        ->and(strlen($content))->toBe(939)
        ->and(substr($content, 1, 4))->toBe(' 172')
        ->and(substr($content, 23, 10))->toBe('  20260821')
        ->and(substr($content, 33, 8))->toBe('  143015')
        ->and(substr($content, 41, 12))->toBe(' 00482854000')
        ->and(substr($content, 184, 35))->toBe('OMEST SRL                          ')
        ->and(substr($content, 219, 35))->toBe('Via L. Negrelli 15                 ')
        ->and(substr($content, 254, 9))->toBe('39100    ')
        ->and(substr($content, 298, 2))->toBe('BZ')
        ->and($content)->toContain('merce varia')
        ->and($content)->toContain(' 00002')
        ->and($content)->toContain('  00021,5')
        ->and($content)->toContain('OLP202600044200');
});

test('a consignee renders the consignee block, none leaves it blank', function (): void {
    $generator = new FnvaoFileGenerator;

    $withConsignee = $generator->generate(makeBooking([
        'consignee' => new AddressData(
            companyName: 'OMEST / EUROPACCO',
            street: 'Via L. Negrelli 15',
            zipcode: '39100',
            city: 'Bolzano',
            province: 'BZ',
        ),
    ]))->content;

    $withoutConsignee = $generator->generate(makeBooking())->content;

    expect(substr($withConsignee, 366, 35))->toBe('OMEST / EUROPACCO                  ')
        ->and(substr($withConsignee, 436, 9))->toBe('39100    ')
        ->and(substr($withoutConsignee, 366, 119))->toBe(str_repeat(' ', 119));
});

test('sms notification fields follow the request flag', function (): void {
    $generator = new FnvaoFileGenerator;

    $withSms = $generator->generate(makeBooking(['requestSms' => true, 'smsPhone' => '+39 333 1234567']))->content;
    $withoutSms = $generator->generate(makeBooking())->content;

    expect(substr($withSms, -60, 16))->toBe('+39 333 1234567 ')
        ->and(substr($withSms, -44, 1))->toBe(' ')
        ->and(substr($withoutSms, -60, 16))->toBe(str_repeat(' ', 16))
        ->and(substr($withoutSms, -44, 1))->toBe('N');
});

test('a second street line takes the first note field and pushes the comment', function (): void {
    $generator = new FnvaoFileGenerator;

    $comment = str_repeat('a', 40).str_repeat('b', 20);

    $singleLine = $generator->generate(makeBooking(['comment' => $comment]))->content;
    $twoLines = $generator->generate(makeBooking([
        'comment' => $comment,
        'senderStreetLines' => ['Via L. Negrelli 15', 'Zona Industriale'],
    ]))->content;

    expect(substr($singleLine, 606, 35))->toBe(str_repeat('a', 35))
        ->and(substr($singleLine, 641, 35))->toBe(str_repeat('a', 5).str_repeat('b', 20).str_repeat(' ', 10))
        ->and(substr($twoLines, 641, 35))->toBe(str_repeat('a', 35));
});

test('pallets flag the record and count into the colli total', function (): void {
    $generator = new FnvaoFileGenerator;

    $withPallets = $generator->generate(makeBooking(['palletCount' => 3]))->content;
    $withoutPallets = $generator->generate(makeBooking())->content;

    expect($withPallets)->toContain(' 00005')
        ->and(substr($withPallets, -243, 1))->toBe('S')
        ->and(substr($withoutPallets, -243, 1))->toBe(' ');
});

test('non-ascii characters are transliterated', function (): void {
    $booking = makeBooking([
        'sender' => new AddressData(
            companyName: 'Straße GmbH',
            street: 'Hauptstraße 1',
            zipcode: '39100',
            city: 'BOLZANO',
            countryCode: 'it',
            province: 'BZ',
        ),
        'senderStreetLines' => ['Hauptstrae 1'],
    ]);

    $content = (new FnvaoFileGenerator)->generate($booking)->content;

    expect($content)->not->toContain('ß')
        ->and(mb_check_encoding($content, 'ASCII'))->toBeTrue();
});

test('the filename carries the export timestamp and a random suffix', function (): void {
    expect((new FnvaoFileGenerator)->filename('2026-08-21 14:30:15'))
        ->toMatch('/^fnvao_20260821_143015\d{5}\.txt$/');
});
