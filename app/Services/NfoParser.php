<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

class NfoParser
{
    public function parseFile(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        return $this->parse($contents);
    }

    public function parse(string $contents): array
    {
        $contents = $this->toUtf8($contents);

        return $this->parseXml($contents) ?: $this->parseText($contents);
    }

    private function parseXml(string $contents): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new DOMXPath($document);
        $values = [];

        foreach ($xpath->query('//Data') as $data) {
            if (! $data instanceof DOMElement) {
                continue;
            }

            $key = $this->directChildValue($data, 'Elemento');
            $value = $this->cleanValue($this->directChildValue($data, 'Valor'));

            if ($key !== '' && $value !== '' && $value !== 'No disponible') {
                $values[$this->normalizeKey($key)] = $value;
            }
        }

        $summaryRecords = $this->categoryRecords($xpath, 'Resumen del sistema');
        $diskRecords = $this->categoryRecords($xpath, 'Discos');
        $displayRecords = $this->categoryRecords($xpath, 'Pantalla');
        $networkRecords = $this->categoryRecords($xpath, 'Adaptador');
        $realNetwork = function (array $record): bool {
            $name = strtolower(($record['nombre'] ?? '').' '.($record['tipo de producto'] ?? ''));

            return ! Str::contains($name, ['kernel debug', 'wan miniport', 'virtual', 'bluetooth']);
        };
        $summaryValue = fn (array $keys): string => $this->firstRecordValue($summaryRecords, $keys)
            ?: $this->value($values, $keys);

        $diskModel = $this->firstRecordValue($diskRecords, ['Modelo']);
        $diskSize = trim(explode('(', $this->firstRecordValue($diskRecords, ['Tamano', 'Tamaño']))[0]);
        $motherboard = implode(' ', array_filter([
            $summaryValue(['Fabricante de la placa base', 'BaseBoard Manufacturer']),
            $summaryValue(['Producto de placa base', 'Producto placa base', 'BaseBoard Product']),
        ]));

        return $this->withoutEmpty([
            'name' => $summaryValue(['Modelo del sistema', 'System Model']),
            'model' => $summaryValue(['Modelo del sistema', 'System Model']),
            'brand' => $summaryValue(['Fabricante del sistema', 'System Manufacturer']),
            'processor' => $this->summarizeProcessor($summaryValue(['Procesador', 'Processor'])),
            'ram' => $summaryValue(['Memoria fisica instalada (RAM)', 'Installed Physical Memory']),
            'storage' => implode(' - ', array_filter([$diskModel, $diskSize])),
            'os' => $summaryValue(['Nombre del SO', 'OS Name']),
            'os_version' => $summaryValue(['Version', 'Versión']),
            'architecture' => $summaryValue(['Tipo de sistema', 'System Type']),
            'bios' => implode(' - ', array_filter([
                $summaryValue(['Modo de BIOS', 'BIOS Mode']),
                $summaryValue(['Version y fecha de BIOS', 'BIOS Version']),
            ])),
            'motherboard' => $motherboard,
            'gpu' => $this->firstRecordValue($displayRecords, ['Nombre', 'Descripcion de adaptador']),
            'network_adapter' => $this->firstRecordValue($networkRecords, ['Tipo de producto', 'Nombre'], $realNetwork),
            'mac_address' => preg_replace('/[^0-9A-Fa-f:.-]/', '', $this->firstRecordValue($networkRecords, ['Direccion MAC', 'MAC Address'], $realNetwork)),
            'secure_boot' => $summaryValue(['Estado de arranque seguro', 'Secure Boot State']),
            'tpm' => $summaryValue(['TPM', 'Modulo de plataforma segura']),
        ]);
    }

    private function parseText(string $contents): array
    {
        $values = [];

        foreach (preg_split('/\r?\n/', $contents) ?: [] as $line) {
            if (preg_match('/^\s*([^:=]+)\s*[:=]\s*(.+?)\s*$/u', $line, $matches)) {
                $values[$this->normalizeKey($matches[1])] = $this->cleanValue($matches[2]);
            }
        }

        $model = $this->value($values, ['Modelo del sistema', 'System Model']);

        return $this->withoutEmpty([
            'name' => $model,
            'model' => $model,
            'brand' => $this->value($values, ['Fabricante del sistema', 'System Manufacturer']),
            'processor' => $this->summarizeProcessor($this->value($values, ['Procesador', 'Processor'])),
            'ram' => $this->value($values, ['Memoria fisica instalada (RAM)', 'Installed Physical Memory']),
            'storage' => $this->value($values, ['Disco', 'Disk', 'Modelo de disco', 'Disk Model']),
            'os' => $this->value($values, ['Nombre del SO', 'OS Name']),
            'os_version' => $this->value($values, ['Version']),
            'architecture' => $this->value($values, ['Tipo de sistema', 'System Type']),
            'bios' => $this->value($values, ['Version y fecha de BIOS', 'BIOS Version']),
            'motherboard' => $this->value($values, ['Producto de placa base', 'BaseBoard Product']),
            'gpu' => $this->value($values, ['Tarjeta grafica', 'Graphics', 'Display']),
            'network_adapter' => $this->value($values, ['Adaptador de red', 'Network Adapter']),
            'mac_address' => $this->value($values, ['Direccion MAC', 'MAC Address']),
            'secure_boot' => $this->value($values, ['Estado de arranque seguro', 'Secure Boot State']),
            'tpm' => $this->value($values, ['TPM']),
        ]);
    }

    private function categoryRecords(DOMXPath $xpath, string $categoryName): array
    {
        foreach ($xpath->query('//Category') as $category) {
            if (! $category instanceof DOMElement || $this->normalizeKey($category->getAttribute('name')) !== $this->normalizeKey($categoryName)) {
                continue;
            }

            $records = [];
            $current = [];

            foreach ($category->childNodes as $data) {
                if (! $data instanceof DOMElement || $data->tagName !== 'Data') {
                    continue;
                }

                $key = $this->normalizeKey($this->directChildValue($data, 'Elemento'));
                $value = $this->cleanValue($this->directChildValue($data, 'Valor'));

                if ($key === '') {
                    if ($current !== []) {
                        $records[] = $current;
                        $current = [];
                    }
                    continue;
                }

                if ($value !== '' && $value !== 'No disponible') {
                    $current[$key] = $value;
                }
            }

            if ($current !== []) {
                $records[] = $current;
            }

            return $records;
        }

        return [];
    }

    private function firstRecordValue(array $records, array $keys, ?callable $predicate = null): string
    {
        foreach ($records as $record) {
            if ($predicate && ! $predicate($record)) {
                continue;
            }

            foreach ($keys as $key) {
                $value = $record[$this->normalizeKey($key)] ?? '';
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function value(array $values, array $keys): string
    {
        foreach ($keys as $key) {
            $wanted = $this->normalizeKey($key);

            if (filled($values[$wanted] ?? null)) {
                return $values[$wanted];
            }

            foreach ($values as $storedKey => $value) {
                if (str_contains($storedKey, $wanted) && filled($value)) {
                    return $value;
                }
            }
        }

        return '';
    }

    private function directChildValue(DOMElement $element, string $tagName): string
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                return trim($child->textContent);
            }
        }

        return '';
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/u', ' ', Str::ascii($value)) ?? ''));
    }

    private function cleanValue(string $value): string
    {
        return trim(preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $value) ?? '');
    }

    private function summarizeProcessor(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', explode(',', $value)[0]) ?? '');
    }

    private function withoutEmpty(array $data): array
    {
        return array_filter($data, fn ($value) => filled($value));
    }

    private function toUtf8(string $contents): string
    {
        if (mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        $encoding = mb_detect_encoding($contents, ['UTF-16LE', 'UTF-16BE', 'Windows-1252', 'ISO-8859-1'], true);

        return $encoding ? mb_convert_encoding($contents, 'UTF-8', $encoding) : $contents;
    }
}
