<?php

namespace Tests\Unit;

use App\Services\NfoParser;
use PHPUnit\Framework\TestCase;

class NfoParserTest extends TestCase
{
    public function test_it_extracts_common_msinfo_specifications(): void
    {
        $result = (new NfoParser)->parse($this->sampleNfo());

        $this->assertSame('ThinkCentre M70Q', $result['name']);
        $this->assertSame('LENOVO', $result['brand']);
        $this->assertSame('Intel Core i5-14500T', $result['processor']);
        $this->assertSame('16.0 GB', $result['ram']);
        $this->assertSame('NVMe Test - 476.94 GB', $result['storage']);
        $this->assertSame('Windows 11 Pro', $result['os']);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $result['mac_address']);
    }

    private function sampleNfo(): string
    {
        return <<<'XML'
<?xml version="1.0"?>
<MsInfo>
  <Category name="Resumen del sistema">
    <Data><Elemento>Nombre del SO</Elemento><Valor>Windows 11 Pro</Valor></Data>
    <Data><Elemento>Version</Elemento><Valor>10.0.26100</Valor></Data>
    <Data><Elemento>Fabricante del sistema</Elemento><Valor>LENOVO</Valor></Data>
    <Data><Elemento>Modelo del sistema</Elemento><Valor>ThinkCentre M70Q</Valor></Data>
    <Data><Elemento>Procesador</Elemento><Valor>Intel Core i5-14500T, 14 nucleos</Valor></Data>
    <Data><Elemento>Memoria fisica instalada (RAM)</Elemento><Valor>16.0 GB</Valor></Data>
  </Category>
  <Category name="Discos">
    <Data><Elemento>Modelo</Elemento><Valor>NVMe Test</Valor></Data>
    <Data><Elemento>Tamano</Elemento><Valor>476.94 GB (512,000 bytes)</Valor></Data>
  </Category>
  <Category name="Adaptador">
    <Data><Elemento>Nombre</Elemento><Valor>Intel Ethernet</Valor></Data>
    <Data><Elemento>Tipo de producto</Elemento><Valor>Intel Ethernet I219-LM</Valor></Data>
    <Data><Elemento>Direccion MAC</Elemento><Valor>AA:BB:CC:DD:EE:FF</Valor></Data>
  </Category>
</MsInfo>
XML;
    }
}
