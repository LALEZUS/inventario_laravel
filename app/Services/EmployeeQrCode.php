<?php

namespace App\Services;

use App\Models\Employee;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\PngWriter;

class EmployeeQrCode
{
    public function dataUri(Employee $employee): string
    {
        $contact = "BEGIN:VCARD\nVERSION:3.0\nFN:{$this->escape($employee->full_name)}\n";

        if (filled($employee->phone_number)) {
            $contact .= 'TEL;TYPE=CELL:'.$this->escape($employee->phone_number)."\n";
        }

        if (filled($employee->email_corporate)) {
            $contact .= 'EMAIL;TYPE=WORK:'.$this->escape($employee->email_corporate)."\n";
        }

        $contact .= "END:VCARD";
        $qrCode = new QrCode(
            data: $contact,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 360,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $logoPath = public_path('images/rayito_n.png');
        $logo = is_file($logoPath) ? new Logo($logoPath, 62, 62) : null;
        $png = (new PngWriter())->write($qrCode, $logo)->getString();

        return 'data:image/png;base64,'.base64_encode($this->cropWhiteMargin($png));
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], trim($value));
    }

    private function cropWhiteMargin(string $png): string
    {
        $source = imagecreatefromstring($png);
        if ($source === false) {
            return $png;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $left = $width;
        $top = $height;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($source, $x, $y);
                if ((($rgb >> 16) & 0xff) < 245 || (($rgb >> 8) & 0xff) < 245 || ($rgb & 0xff) < 245) {
                    $left = min($left, $x);
                    $top = min($top, $y);
                    $right = max($right, $x);
                    $bottom = max($bottom, $y);
                }
            }
        }

        if ($right < $left || $bottom < $top) {
            imagedestroy($source);
            return $png;
        }

        $padding = 12;
        $codeWidth = $right - $left + 1;
        $codeHeight = $bottom - $top + 1;
        $cropped = imagecreatetruecolor($codeWidth + ($padding * 2), $codeHeight + ($padding * 2));
        $white = imagecolorallocate($cropped, 255, 255, 255);
        imagefill($cropped, 0, 0, $white);
        imagecopy($cropped, $source, $padding, $padding, $left, $top, $codeWidth, $codeHeight);
        ob_start();
        imagepng($cropped);
        $result = (string) ob_get_clean();
        imagedestroy($cropped);
        imagedestroy($source);

        return $result;
    }
}
