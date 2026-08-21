<?php

namespace SmartDato\BrtDocumentoDiRitiro\Generator;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use SmartDato\BrtDocumentoDiRitiro\Data\BookingData;
use SmartDato\BrtDocumentoDiRitiro\FnvaoFile;
use SmartDato\BrtDocumentoDiRitiro\Support\Numbers;
use SmartDato\BrtDocumentoDiRitiro\Support\Text;

/**
 * Renders the fixed-width FNVAO pickup booking record BRT's VAS system parses.
 * Field names and offsets follow BRT's VAO tracciato; every padding quirk is
 * kept byte-identical to the historical implementation.
 */
class FnvaoFileGenerator
{
    public function generate(BookingData $booking): FnvaoFile
    {
        return new FnvaoFile(
            content: $this->content($booking),
            filename: $this->filename($booking->exportedAt),
        );
    }

    public function filename(DateTimeInterface|string|null $exportedAt): string
    {
        return 'fnvao_'.Carbon::parse($exportedAt)->format('Ymd_His').random_int(10000, 99999).'.txt';
    }

    private function content(BookingData $booking): string
    {
        $exportedAt = Carbon::parse($booking->exportedAt);
        $pickupAfter = Carbon::parse($booking->pickupAfter);
        $sender = $booking->sender;

        $country = ($sender->countryCode === 'it') ? '' : strtoupper($sender->countryCode);
        $weight = Numbers::format($booking->weight, 1);

        $countTotalColli = $booking->parcelCount + ($booking->palletCount ?? 0);
        $payFlag = 'O';
        $palletFlag = (($booking->palletCount ?? 0) > 0) ? 'S' : ' ';

        $streetLines = array_values($booking->senderStreetLines);

        $content = '';
        $content .= Text::padLeft('', 1, ' '); // VAOATB, 1-1, blank
        $content .= Text::padLeft(Text::maxLength('172', 3), 3, ' ', ' '); // VAOPOE, 2-5, brt code
        $content .= Text::padLeft('', 2, '0', '  '); // VAONSR, 6-9
        $content .= Text::padLeft('', 7, '0', ' '); // VAONOR, 10 - 17
        $content .= Text::padLeft('', 2, '0', '  '); // VAONRV, 18 - 21
        $content .= Text::padLeft('M', 1, ' '); // VAOTOR, 22 - 22,
        $content .= Text::padLeft(' ', 1, ' '); // VAOTCO, 23 - 24
        $content .= Text::padLeft($exportedAt->format('Ymd'), 8, ' ', '  '); // VAODAO, 24 - 33
        $content .= Text::padLeft($exportedAt->format('His'), 6, ' ', '  '); // VAOOAO, 34 - 41
        $content .= Text::padLeft(Text::maxLength($booking->clientCode.'000', 11), 11, '0', ' '); // VAOCOR, 42 - 53
        $content .= Text::padLeft('', 35, ' '); // VAORSO, 54 - 88
        $content .= Text::padLeft('', 35, ' '); // VAOINO, 89 - 123
        $content .= Text::padLeft('', 9, ' '); // VAOCAO, 124 - 132
        $content .= Text::padLeft('', 35, ' '); // VAOLOO, 133 - 167
        $content .= Text::padLeft('', 2, ' '); // VAOPRO, 168 - 169
        $content .= Text::padLeft('', 3, ' '); // VAONAO, 170 - 172
        $content .= Text::padLeft(Text::maxLength('', 10), 10, '0', '  '); // VAOCRA, 173 - 184
        $content .= Text::padRight(Text::maxLength(Text::brtString($sender->companyOrPersonName()), 35), 35, ' '); // VAORSR 185 - 219
        $content .= Text::padRight(Text::maxLength(Text::brtString($streetLines[0] ?? ''), 35), 35, ' '); // VAOINR, 220 - 254
        $content .= Text::padRight(Text::maxLength(Text::brtString($sender->zipcode), 9), 9, ' '); // VAOCAR, 255 - 263
        $content .= Text::padRight(Text::maxLength(Text::brtString($sender->city), 35), 35, ' '); // VAOLOR, 264 - 298
        $content .= Text::padRight(Text::maxLength($sender->province, 2), 2, ' '); // VAOPRR 299 - 300
        $content .= Text::padRight(Text::maxLength($country, 3), 3, ' '); // VAONAR 301 - 303
        $content .= Text::padRight(Text::maxLength(Text::brtString($sender->personName), 35), 35, ' '); // VAORER 304 - 338
        $content .= Text::padRight(Text::maxLength($sender->phone, 16), 16, ' '); // VAOTER, 339 - 354
        $content .= Text::padRight('', 12, ' '); // VAOCRC, 355 - 366

        $consignee = $booking->consignee;
        if ($consignee !== null) {
            $content .= Text::padRight(Text::maxLength(Text::brtString($consignee->companyOrPersonName()), 35), 35, ' '); // VAORSC, 367 - 401
            $content .= Text::padRight(Text::maxLength(Text::brtString($consignee->street), 35), 35, ' '); // VAOINC, 402 - 436
            $content .= Text::padRight(Text::maxLength(Text::brtString($consignee->zipcode), 9), 9, ' '); // VAOCAC, 437 - 445
            $content .= Text::padRight(Text::maxLength(Text::brtString($consignee->city), 35), 35, ' '); // VAOLOC, 446 - 480
            $content .= Text::padRight(Text::maxLength(Text::brtString($consignee->province), 2), 2, ' '); // VAOPRC, 481 - 482
            $content .= Text::padRight('', 3, ' '); // VAONAC, 483 - 485
        } else {
            $content .= Text::padRight('', 35, ' '); // VAORSC, 367 - 401
            $content .= Text::padRight('', 35, ' '); // VAOINC, 402 - 436
            $content .= Text::padRight('', 9, ' '); // VAOCAC, 437 - 445
            $content .= Text::padRight('', 35, ' '); // VAOLOC, 446 - 480
            $content .= Text::padRight('', 2, ' '); // VAOPRC, 481 - 482
            $content .= Text::padRight('', 3, ' '); // VAONAC, 483 - 485
        }
        $content .= Text::padLeft('', 1, ' '); // VAOFFD, 486 - 486
        $content .= Text::padLeft($pickupAfter->format('Ymd'), 8, ' ', '  '); // VAODAR, 487 - 496
        $content .= Text::padLeft($pickupAfter->format('Hi'), 4, ' ', '  '); // VAOORR, 497 - 502
        $content .= Text::padLeft(' ', 1, ' '); // VAORMP, 503 - 503
        $content .= Text::padRight('merce varia', 35, ' '); // VAONAM, 504 - 538
        $content .= Text::padLeft($countTotalColli, 5, '0', ' '); // VAONCL 539 - 544
        $content .= Text::padLeft(Text::maxLength($weight, 7), 7, '0', '  '); // VAOPKG
        $content .= Text::padLeft(Numbers::filled($booking->volume, 2, 3), 7, ' '); // VAOVLM, 554 560
        $content .= Text::padLeft($booking->palletCount ?? '', 5, '0', ' '); // VAOBNC, 561 566
        $content .= Text::padLeft('0', 5, '0', ' '); // VAOBLC
        $content .= Text::padLeft('0', 5, '0', ' '); // VAOATT
        $content .= Text::padLeft('0', 5, '0', ' '); // VAOMTC
        $content .= Text::padLeft($payFlag, 1, ' '); // VAOPAG
        $content .= Text::padLeft('0', 7, '0', ' '); // VAOKSC, 586 593
        $content .= Text::padLeft(' ', 3, ' '); // VAOCTR, 594 596
        $content .= Text::padLeft(Text::maxLength('000', 3), 3, ' ', ' '); // VAOPOR, 597 600
        $content .= Text::padLeft(Text::maxLength('', 2), 2, ' '); // VAOZOR, 601 602
        $content .= Text::padLeft(Text::maxLength('', 3), 3, ' ', ' '); // VAOPOC, 603 606

        if (count($streetLines) > 1) {
            $content .= Text::padRight(substr(Text::brtString($streetLines[1]), 35, 35), 35, ' '); // VAONO1
            $content .= Text::padRight(substr(Text::brtString($booking->comment), 0, 35), 35, ' '); // VAONO2, 642 676
        } else {
            $content .= Text::padRight(substr(Text::brtString($booking->comment), 0, 35), 35, ' '); // VAONO1
            $content .= Text::padRight(substr(Text::brtString($booking->comment), 35, 35), 35, ' '); // VAONO2, 642 676
        }

        $content .= Text::padLeft('N', 1, ' '); // VAODDT
        $content .= Text::padLeft(Text::maxLength($booking->reference, $booking->referenceLength), $booking->referenceLength, ' '); // VAORFA
        $content .= Text::padLeft('', 3, '0', ' '); // VAOSTO
        $content .= Text::padLeft($palletFlag, 1, ' '); // VAOSPI, 697
        $content .= Text::padLeft('', 7, ' '); // VAOFLO, 698 704
        $content .= Text::padLeft('N', 1, ' '); // VAOFLO, 705 705
        $content .= Text::padLeft('', 12, ' '); // VAOFLO, 706 717
        $content .= Text::padLeft('', 20, ' '); // VAOFLO2, 737
        $content .= Text::padLeft('', 1, ' '); // VAOTSPS, 738
        $content .= Text::padRight($booking->confirmEmail, 60, ' '); // VAONOTE
        $content .= Text::padLeft($this->cleanTime($booking->openingMorningFrom), 4, ' ', ' '); // VAOORDA1
        $content .= Text::padLeft($this->cleanTime($booking->openingMorningTo), 4, ' ', ' '); // VAOORAA1
        $content .= Text::padLeft($this->cleanTime($booking->openingAfternoonFrom), 4, ' ', ' '); // VAOORDA2
        $content .= Text::padLeft($this->cleanTime($booking->openingAfternoonTo), 4, ' ', ' '); // VAOORAA2, 818
        $content .= Text::padRight('', 60, ' '); // VAOEMLR
        $content .= Text::padLeft('N', 1, ' '); // VAOEMLRF

        if ($booking->requestSms) {
            $content .= Text::padRight(Text::maxLength($booking->smsPhone, 16), 16, ' '); // VAOSMSR
            $content .= Text::padLeft('', 1, ' '); // VAOSMSRF
        } else {
            $content .= Text::padRight('', 16, ' '); // VAOSMSR
            $content .= Text::padLeft('N', 1, ' '); // VAOSMSRF
        }
        $content .= Text::padLeft('', 15, ' '); // VAOORIP
        $content .= Text::padLeft('', 26, ' '); // VAODOIO

        $content .= "\r\n";

        return iconv('UTF-8', 'ASCII//TRANSLIT', $content);
    }

    private function cleanTime(?string $time): string
    {
        return str_replace(':', '', (string) $time);
    }
}
