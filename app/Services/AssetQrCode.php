<?php

namespace App\Services;

use App\Models\HardwareAsset;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class AssetQrCode
{
    public function png(HardwareAsset $computer): string
    {
        $url = $this->url($computer);
        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 420,
            margin: 18,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return (new PngWriter())->write($qrCode)->getString();
    }

    public function url(HardwareAsset $computer): string
    {
        return route('computers.show', ['computer' => $computer, 'source' => 'qr']);
    }
}
