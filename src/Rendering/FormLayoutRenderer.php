<?php

namespace SmartDato\BrtDocumentoDiRitiro\Rendering;

use Exception;
use Illuminate\Support\Carbon;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use SmartDato\BrtDocumentoDiRitiro\Data\AddressData;
use SmartDato\BrtDocumentoDiRitiro\Support\Assets;
use SmartDato\BrtDocumentoDiRitiro\Support\BarcodeImage;
use SmartDato\BrtDocumentoDiRitiro\Support\BorderoGrouper;
use SmartDato\BrtDocumentoDiRitiro\Support\Numbers;
use SmartDato\BrtDocumentoDiRitiro\Support\Text;

class FormLayoutRenderer
{
    public function render(RenderContext $context, BorderoGrouper $grouper): Image
    {
        $factor = $context->factor;
        $pickup = $context->pickup;
        $shipment = $context->shipment;
        $parcel = $context->parcel;
        $sideText = $context->sideText;
        $measurement = $context->measurement;
        $pageCounter = $context->pageCounter;

        $img = ImageManagerStatic::canvas((int) (21.01 * $factor), (int) (29.7 * $factor), '#FFF');

        $gothic = Assets::font('franklin_gothic');
        $verdanaMono = Assets::font('verdana_mono');
        $verdanaBold = Assets::font('verdana_bold');
        $verdanaItalic = Assets::font('verdana_italic');

        $x_start = 3.67;
        $x_start_padding = $x_start + 0.2;
        $y_start = 2.53;
        $x_end = 18.73;
        $y_end = $y_start + 25.85;
        $border = 2;

        $senderAddress = $context->sender;
        $deliveryAddress = $shipment->deliveryAddress;

        if ($deliveryAddress->countryCode != 'it') {
            $fallback = config('brt-documento-di-ritiro.fallback_delivery_address');
            $deliveryAddress = new AddressData(
                personName: $context->branding->fallbackRecipientName,
                companyName: $fallback['company_name'] ?? null,
                street: $fallback['street'] ?? null,
                zipcode: $fallback['zipcode'] ?? null,
                city: $fallback['city'] ?? null,
                countryCode: $fallback['country_code'] ?? 'it',
                countryName: $fallback['country_name'] ?? null,
                phone: $fallback['phone'] ?? null,
                email: $fallback['email'] ?? null,
            );
        }

        $responseFont = $verdanaMono;
        $responseSize = 0.3;

        if (! is_null($parcel)) {
            $parcelPageCount = $grouper->pageCountFor($shipment);
            $parcel_nr_str = $pageCounter.' / '.$parcelPageCount;
            $sideText .= ' '.$parcel_nr_str;
        } else {
            $parcel_nr_str = (string) $pageCounter;
        }

        // global
        $img->rectangle($x_start * $factor, $y_start * $factor, $x_end * $factor, $y_end * $factor, function ($draw): void {
            $draw->border(3, '#000');
        });

        // side
        if ($shipment->useBarcodeSideLayout) {
            if ($context->branding->sideLogoPath !== null) {
                $sideLogo = ImageManagerStatic::make($context->branding->sideLogoPath);
                $sideLogo->resize(3.18 / 2 * $factor, 18.87 / 2 * $factor);
                $img->insert($sideLogo, 'top-left', (int) (1.7 * $factor), (int) (17 * $factor));
            }

            $resizeRef = 2.0;
            $barcode = $shipment->key;
            if (! is_null($parcel)) {
                $barcode = $parcel->key;
            }
            $barcodeRef = ImageManagerStatic::make(BarcodeImage::resource($barcode, 0.1 * $factor));
            $barcodeRef->resize((15.05 * $factor) / $resizeRef, (5 * $factor) / $resizeRef);

            $barcodeRef->rotate(90);
            $img->insert($barcodeRef, 'top-left', (int) (0.9 * $factor), (int) (4 * $factor));

            $img->text($sideText, ($x_start - 2.0) * $factor, ($y_start + 11.0) * $factor, function ($font) use ($gothic, $factor): void {
                $font->file($gothic);
                $font->size(1.2 * $factor);
                $font->color('#000');
                $font->align('right');
                $font->valign('bottom');
                $font->angle(90);
            });

            $text = Text::cleanAscii($shipment->deliveryAddress->companyOrPersonName()).' ['.strtoupper($shipment->deliveryAddress->countryCode).']';
            $img->text($text, ($x_start - 0.0) * $factor, ($y_start + 12.0) * $factor, function ($font) use ($gothic, $factor): void {
                $font->file($gothic);
                $font->size(0.5 * $factor);
                $font->color('#000');
                $font->align('right');
                $font->valign('bottom');
                $font->angle(90);
            });
        } else {
            if ($context->branding->sideLogoPath !== null) {
                $sideLogo = ImageManagerStatic::make($context->branding->sideLogoPath);
                $sideLogo->resize(3.35 / 1.2 * $factor, 7.66 / 1.2 * $factor);
                $img->insert($sideLogo, 'top-left', (int) (0.9 * $factor), (int) (3 * $factor));
            }

            $img->text($sideText, ($x_start - 1) * $factor, ($y_start + 10.0) * $factor, function ($font) use ($gothic, $factor): void {
                $font->file($gothic);
                $font->size(1.2 * $factor);
                $font->color('#000');
                $font->align('right');
                $font->valign('bottom');
                $font->angle(90);
            });
        }

        $img->rectangle($factor * 0.5, $y_start * $factor, $x_end * $factor, $y_end * $factor, function ($draw): void {
            $draw->border(3, '#000');
        });

        // header
        $img->rectangle($x_start * $factor, $y_start * $factor, $x_end * $factor, ($x_start + 1.91) * $factor, function ($draw) use ($border): void {
            $draw->border($border, '#000');
        });

        $logo = ImageManagerStatic::make(Assets::image('brt_logo'));
        $logo->resize(1.26 * $factor, 2.03 * $factor);
        $img->insert($logo, 'top-right', (int) (3 * $factor), (int) (3 * $factor));

        $logoText = ImageManagerStatic::make(Assets::image('brt_logo_text'));
        $logoText->resize(4.57 * $factor, 1.11 * $factor);
        $img->insert($logoText, 'top-right', (int) (5 * $factor), (int) (3.9 * $factor));

        $img->text('DOCUMENTO DI RITIRO', $x_start_padding * $factor, ($y_start + 2) * $factor, function ($font) use ($gothic, $factor): void {
            $font->file($gothic);
            $font->size(0.6 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->rectangle($x_start * $factor, $y_start * $factor, $x_end * $factor, ($x_start + 2.88) * $factor, function ($draw) use ($border): void {
            $draw->border($border, '#000');
        });

        $img->text('NUMERO DOCUMENTO*', $x_start_padding * $factor, ($y_start + 3.3) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text($pickup->documentNumber, $x_start_padding * $factor, ($y_start + 3.8) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->line(($x_start + 7.45) * $factor, ($x_start + 1.91) * $factor, ($x_start + 7.45) * $factor, ($x_start + 2.88) * $factor, function ($draw): void {
            $draw->color('#000');
        });

        $img->text('DATA DOCUMENTO*', ($x_start + 7.6) * $factor, ($y_start + 3.3) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        try {
            $pickup_after = Carbon::parse($pickup->documentDate)->format('d.m.Y');
        } catch (Exception $e) {
            $pickup_after = now()->format('d.m.Y');
        }
        $img->text($pickup_after, ($x_start + 7.6) * $factor, ($y_start + 3.8) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->rectangle($x_start * $factor, $y_start * $factor, $x_end * $factor, ($x_start + 7.11) * $factor, function ($draw) use ($border): void {
            $draw->border($border, '#000');
        });

        $img->text('MITTENTE*', $x_start_padding * $factor, ($y_start + 4.3) * $factor, function ($font) use ($verdanaBold, $factor): void {
            $font->file($verdanaBold);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text(Text::cleanAscii($senderAddress->personOrCompanyName()), $x_start_padding * $factor, ($y_start + 4.8) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        if (Text::cleanAscii($senderAddress->personOrCompanyName()) != Text::cleanAscii($senderAddress->companyOrPersonName())) {
            $img->text(Text::cleanAscii($senderAddress->companyOrPersonName()), $x_start_padding * $factor, ($y_start + 5.4) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
                $font->file($responseFont);
                $font->size($responseSize * $factor);
                $font->color('#000');
                $font->align('left');
                $font->valign('bottom');
            });
        }

        $img->text('COD.CLIENTE BRT: - '.$context->clientCode, ($x_start_padding + 4) * $factor, ($y_start + 4.3) * $factor, function ($font) use ($verdanaBold, $factor): void {
            $font->file($verdanaBold);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('INDIRIZZO*', $x_start_padding * $factor, ($y_start + 5.83) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text(Text::cleanAscii($senderAddress->street), $x_start_padding * $factor, ($y_start + 6.33) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('CAP*', $x_start_padding * $factor, ($y_start + 6.6) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text($senderAddress->zipcode, $x_start_padding * $factor, ($y_start + 6.9) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('LOCALITA*', ($x_start + 3.5) * $factor, ($y_start + 6.6) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text(Text::cleanAscii($senderAddress->city), ($x_start + 3.5) * $factor, ($y_start + 6.9) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('PROV./NAZ.*', $x_start_padding * $factor, ($y_start + 7.2) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text(Text::cleanAscii($senderAddress->countryName), $x_start_padding * $factor, ($y_start + 7.6) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('TEL./CELL.', $x_start_padding * $factor, ($y_start + 8.11) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text($senderAddress->phone, ($x_start + 3.2) * $factor, ($y_start + 8.13) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('DESTINATARIO*', $x_start_padding * $factor, ($y_start + 4.3 + 4.25) * $factor, function ($font) use ($verdanaBold, $factor): void {
            $font->file($verdanaBold);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text(Text::cleanAscii($deliveryAddress->personOrCompanyName()), $x_start_padding * $factor, ($y_start + 4.3 + 4.75) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        if (Text::cleanAscii($deliveryAddress->companyOrPersonName()) != Text::cleanAscii($deliveryAddress->personOrCompanyName())) {
            $img->text(Text::cleanAscii($deliveryAddress->companyOrPersonName()), $x_start_padding * $factor, ($y_start + 4.8 + 4.75) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
                $font->file($responseFont);
                $font->size($responseSize * $factor);
                $font->color('#000');
                $font->align('left');
                $font->valign('bottom');
            });
        }

        $img->text('INDIRIZZO*', $x_start_padding * $factor, ($y_start + 5.83 + 4.25) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text(Text::cleanAscii($deliveryAddress->street), $x_start_padding * $factor, ($y_start + 5.83 + 4.75) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('CAP*', $x_start_padding * $factor, ($y_start + 6.6 + 4.25) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text($deliveryAddress->zipcode, $x_start_padding * $factor, ($y_start + 6.6 + 4.65) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('LOCALITA*', ($x_start + 3.5) * $factor, ($y_start + 6.6 + 4.25) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text(Text::cleanAscii($deliveryAddress->city), ($x_start + 3.5) * $factor, ($y_start + 6.6 + 4.65) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('PROV./NAZ.*', $x_start_padding * $factor, ($y_start + 7.3 + 4.25) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text(Text::cleanAscii($deliveryAddress->countryName), $x_start_padding * $factor, ($y_start + 7.3 + 4.65) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('TEL./CELL.', $x_start_padding * $factor, ($y_start + 8.11 + 4.25) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text($deliveryAddress->phone, ($x_start + 3.2) * $factor, ($y_start + 8.11 + 4.25) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('EMAIL      -->', $x_start_padding * $factor, ($y_start + 8.61 + 4.25) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text($deliveryAddress->email, ($x_start + 3.2) * $factor, ($y_start + 8.61 + 4.32) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->rectangle($x_start * $factor, $y_start * $factor, $x_end * $factor, ($x_start + 12.14) * $factor, function ($draw) use ($border): void {
            $draw->border($border, '#000');
        });

        $img->text('TIPO DI SERVIZIO*', $x_start_padding * $factor, ($y_start + 13.58) * $factor, function ($font) use ($verdanaBold, $factor): void {
            $font->file($verdanaBold);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('P.TO FRANCO', $x_start_padding * $factor, ($y_start + 14.1) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.2 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $franco_x1 = 2.7;
        $franco_x2 = 3.0;
        $img->rectangle(($x_start + $franco_x1) * $factor, ($y_start + 13.9) * $factor, ($x_start + $franco_x2) * $factor, ($y_start + 14.2) * $factor, function ($draw) use ($border): void {
            $draw->border($border, '#000');
        });
        $img->line(($x_start + $franco_x1) * $factor, ($y_start + 13.9) * $factor, ($x_start + $franco_x2) * $factor, ($y_start + 14.2) * $factor, function ($draw): void {
            $draw->color('#000');
        });
        $img->line(($x_start + $franco_x2) * $factor, ($y_start + 13.9) * $factor, ($x_start + $franco_x1) * $factor, ($y_start + 14.2) * $factor, function ($draw): void {
            $draw->color('#000');
        });

        // info table
        $table_info_y1 = 13.28;
        $table_info_line_x1 = 3.61;
        $table_info_line_x2 = 7.5;

        $table_info_line_y1 = 16.8;
        $table_info_rows = 4;
        $table_info_step = ($table_info_line_y1 - $table_info_y1) / $table_info_rows;

        $img->rectangle($x_start * $factor, ($x_start + $table_info_y1) * $factor, $x_end * $factor, ($x_start + $table_info_y1) * $factor, function ($draw) use ($border): void {
            $draw->border($border, '#000');
        });

        for ($i = 1; $i <= $table_info_rows; $i++) {
            $img->rectangle($x_start * $factor, ($x_start + $table_info_y1) * $factor, ($x_start + $table_info_line_x2) * $factor, ($x_start + $table_info_y1 + ($table_info_step * $i)) * $factor, function ($draw) use ($border): void {
                $draw->border($border, '#000');
            });
        }

        // vertical
        $img->line(($x_start + $table_info_line_x1) * $factor, ($x_start + $table_info_y1) * $factor, ($x_start + $table_info_line_x1) * $factor, ($x_start + $table_info_line_y1) * $factor, function ($draw): void {
            $draw->color('#000');
        });

        $img->line(($x_start + $table_info_line_x2) * $factor, ($x_start + $table_info_y1) * $factor, ($x_start + $table_info_line_x2) * $factor, ($x_start + $table_info_line_y1) * $factor, function ($draw): void {
            $draw->color('#000');
        });

        $img->rectangle($x_start * $factor, $y_start * $factor, $x_end * $factor, ($x_start + 16.8) * $factor, function ($draw) use ($border): void {
            $draw->border($border, '#000');
        });

        $table_info_row_text_y1 = 14.7;
        $table_info_row_text_y2 = 15.1;
        $img->text('NUMERO COLLI*', ($x_start_padding + 8) * $factor, ($y_start + $table_info_row_text_y1 + 0.5) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.55 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text($parcel_nr_str, ($x_start_padding + 9) * $factor, ($y_start + $table_info_row_text_y2 + 2) * $factor, function ($font) use ($responseFont, $factor): void {
            $font->file($responseFont);
            $font->size(0.67 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('PESO kg*', ($x_start + 3.75) * $factor, ($y_start + $table_info_row_text_y1) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.15 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $weight = 0;
        if (! is_null($parcel)) {
            $weight = $parcel->weight;
        }
        if (! is_null($measurement)) {
            $weight = $measurement['weight'];
        }
        $img->text(Numbers::format($weight), ($x_start + 3.75) * $factor, ($y_start + $table_info_row_text_y2) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $table_info_row_text_y1 += 0.85;
        $table_info_row_text_y2 += 0.85;
        $img->text('IMPORTO CONTRASSEGNO', $x_start_padding * $factor, ($y_start + $table_info_row_text_y1) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.15 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $cod = $grouper->codFor($shipment);

        $img->text(Numbers::format($cod), $x_start_padding * $factor, ($y_start + $table_info_row_text_y2) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $img->text('MODALITA\' INCASSO C/ASSEGNO', ($x_start + 3.75) * $factor, ($y_start + $table_info_row_text_y1) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.15 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });
        $img->text($shipment->codTypeName ?? '', ($x_start + 3.75) * $factor, ($y_start + $table_info_row_text_y2) * $factor, function ($font) use ($responseFont, $factor): void {
            $font->file($responseFont);
            $font->size(0.15 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $table_info_row_text_y1 += 0.85;
        $table_info_row_text_y2 += 0.85;
        $img->text('VOLUME m3', $x_start_padding * $factor, ($y_start + $table_info_row_text_y1) * $factor, function ($font) use ($verdanaMono, $factor): void {
            $font->file($verdanaMono);
            $font->size(0.15 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $volume = 0;
        if (! is_null($parcel)) {
            $volume = $parcel->volume;
        }
        if (! is_null($measurement)) {
            $volume = $measurement['volume'];
        }
        $img->text(Numbers::format($volume), $x_start_padding * $factor, ($y_start + $table_info_row_text_y2) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $inBehalfOf = 'Ritiro per conto di : '.$shipment->onBehalfOf;

        $img->text($inBehalfOf, $x_start_padding * $factor, ($y_start + 18.5) * $factor, function ($font) use ($responseFont, $responseSize, $factor): void {
            $font->file($responseFont);
            $font->size($responseSize * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        $firmaMittente = str_pad(
            'FIRMA MITTENTE PER ACCETTAZIONE DELLE CONDIZIONI DI TRASPORTO SU '.$context->branding->termsUrl.' ',
            135,
            '_'
        );
        $img->text($firmaMittente, $x_start_padding * $factor, ($y_start + 25.7) * $factor, function ($font) use ($verdanaItalic, $factor): void {
            $font->file($verdanaItalic);
            $font->size(0.15 * $factor);
            $font->color('#000');
            $font->align('left');
            $font->valign('bottom');
        });

        return $img;
    }
}
