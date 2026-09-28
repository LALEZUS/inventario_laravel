<?php

namespace App\Services;

use App\Models\Employee;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
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
            margin: 4,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return 'data:image/png;base64,'.base64_encode((new PngWriter())->write($qrCode)->getString());
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], trim($value));
    }
}
