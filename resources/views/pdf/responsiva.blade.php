<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #111; font-family: DejaVu Sans, sans-serif; font-size: 9.2pt; line-height: 1.28; }
    .page { width: 215.9mm; height: 271.5mm; page-break-after: always; overflow: hidden; }
    .page:last-child { page-break-after: auto; }
    .page-header { height: 38.1mm; font-size: 1px; line-height: 1px; }
    .page-content { height: 198mm; padding: 0 19mm; overflow: hidden; }
    .page-footer { height: 35.4mm; font-size: 1px; line-height: 1px; }
    .page-header img, .page-footer img { display: block; width: 215.9mm; height: 100%; }
    h1, h2 { margin: 0; text-align: center; font-weight: bold; }
    h1 { font-size: 13pt; }
    h2 { margin-top: 3mm; font-size: 13pt; }
    .date { margin: 8mm 0 10mm; text-align: right; }
    .legal { margin: 0 0 3.2mm; text-align: justify; }
    .signatures { width: 100%; margin-top: 1mm; border-collapse: collapse; table-layout: fixed; }
    .signatures td { width: 33.333%; padding: 0 4mm; text-align: center; vertical-align: top; }
    .signature-label { margin-bottom: 8mm; }
    .signature-line { border-top: .3mm solid #333; }
    .signature-name { margin-top: 2mm; }
    .signature-role { margin-top: 1.5mm; }
    .report-title { font-size: 13pt; }
    .report-date { margin: 8mm 0 4mm; text-align: right; }
    .report-intro { margin: 0 0 10mm; }
    .report-field { margin: 0 0 4mm; }
    .photo-list { margin-top: 5mm; }
    .photo-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .photo-table td { width: 50%; padding: 0 2.5mm; text-align: center; vertical-align: middle; }
    .photo-table.single-photo td { width: 100%; padding: 0; }
    .photo-frame { height: 104mm; line-height: 104mm; text-align: center; }
    .photo-frame img { max-width: 82mm; max-height: 102mm; vertical-align: middle; }
    .photo-table.large .photo-frame img, .photo-table.single-photo .photo-frame img { max-width: 154mm; }
    .empty-photos { margin: 32mm 0; text-align: center; color: #555; }
    .report-continuation .page-content { height: 188mm; padding-top: 10mm; }
    .report-continuation .photo-frame { height: 136mm; line-height: 136mm; }
    .report-continuation .photo-frame img { max-height: 134mm; }
    .report-signatures { margin-top: 5mm; }
</style>
</head>
<body class="{{ $letterhead ? 'with-letterhead' : 'without-letterhead' }}">
<section class="page legal-page">
    <div class="page-header">@if($letterhead)&nbsp;@endif</div>
    <div class="page-content">
    <h1>CARTA RESPONSIVA</h1>
    <h2>ENTREGA DE EQUIPO DE CÓMPUTO AL TRABAJADOR</h2>
    <p class="date">Guadalajara, Jalisco a {{ $documentDate }}</p>

    <p class="legal">Por medio de la presente el que suscribe declara recibir como herramienta de trabajo un Equipo Laptop marca <strong>{{ $computer->brand ?: 'SIN MARCA' }}</strong> modelo <strong>{{ $equipmentModel }}</strong>, número de serie <strong>{{ $computer->serial ?: 'SIN SERIE' }}</strong> y con valor de <strong>{{ $formattedValue }}</strong>, comprometiéndose a mantenerlo en el estado en el que lo recibe, cuidando de dicho material como si el mismo fuera de su propiedad, en el entendido de que en caso de que el mismo sufra cualquier daño ocasionado por su dolo o negligencia se hará responsable de la reparación del mismo.</p>
    <p class="legal">En caso de que, por causas inherentes al uso y desgaste normales del equipo, el mismo requiera cualquier reparación, el que suscribe notificará tal circunstancia a <strong>Grupo Enertec S.A. de C.V. al Área de T.I.</strong> para que la misma le indique las condiciones en las que las reparaciones o trabajo de mantenimiento sobre el mismo habrán de realizarse.</p>
    <p class="legal">El suscriptor de este documento reconoce que el equipo que se le entrega sólo podrá ser utilizado para cumplir con las tareas que le encomiende la empresa en su calidad de <strong>PATRÓN</strong> y que no podrá hacer uso del mismo para cuestiones de carácter personal. Asimismo, se compromete a emplear el equipo únicamente de acuerdo con las condiciones y especificaciones que para dichos efectos haga de su conocimiento la empresa, obligándose a no modificarlo ni en el hardware ni en el software, es decir no agregar ni suprimir ningún programa de los que se encuentren cargados originalmente sin el expreso consentimiento por escrito de la empresa.</p>
    <p class="legal">El que suscribe reconoce que los derechos sobre el equipo objeto de la presente corresponden exclusivamente a <strong>Grupo Enertec S.A. de C.V.</strong> en términos del contrato que tiene celebrado con el proveedor del mismo por lo que a la simple solicitud de la empresa se obliga a devolver el equipo que se le entrega a la firma del presente y, en todo caso, al terminar su relación laboral con la compañía dejará de utilizar el mismo haciendo entrega de él al Departamento de Sistemas y Diseño y sin alterar o borrar ningún tipo de archivo o contenido que sea propiedad de la <strong>Grupo Enertec S.A. de C.V.</strong></p>

    @include('pdf._responsiva-signatures', ['recipient' => $employeeName])
    </div>
    <div class="page-footer">@if($letterhead)&nbsp;@endif</div>
</section>

@foreach($photoPages as $pagePhotos)
<section class="page report-page {{ $loop->first ? '' : 'report-continuation' }}">
    @php($pdfPageNumber = $loop->iteration + 1)
    <div class="page-header">@if($letterhead && $pdfPageNumber % 2 === 0)<img src="{{ $letterheadHeaders[$pdfPageNumber] }}" alt="">@elseif($letterhead)&nbsp;@endif</div>
    <div class="page-content">
    @if($loop->first)
        <h1 class="report-title">REPORTE DE EQUIPO DE COMPUTO</h1>
        <p class="report-date">Guadalajara, Jalisco a {{ $documentDate }}</p>
        <p class="report-intro">Por este medio de la presente se anexa el reporte del equipo entregado a {{ $employeeName }}</p>
        <p class="report-field">Equipo: <strong>{{ $equipmentModel }}</strong></p>
        <p class="report-field">Serie: <strong>{{ $computer->serial ?: 'SIN SERIE' }}</strong></p>
        <p class="report-field">Estado: El equipo se entrego con cargador original {{ $computer->brand ?: 'del equipo' }}, se anexan imágenes de como se está entregando el equipo</p>
    @endif

    @if($pagePhotos->isEmpty())
        <p class="empty-photos">No hay fotografías adjuntas a este equipo.</p>
    @else
        <div class="photo-list">
            <table class="photo-table {{ $photoLayout }} {{ $pagePhotos->count() === 1 ? 'single-photo' : '' }}" role="presentation">
                <tr>
                    @foreach($pagePhotos as $photo)
                        <td><div class="photo-frame"><img src="{{ $photo }}" alt="Foto del equipo"></div></td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    @if($loop->last)
        <div class="report-signatures">@include('pdf._responsiva-signatures', ['recipient' => $employeeName])</div>
    @endif
    </div>
    <div class="page-footer">@if($letterhead && $pdfPageNumber % 2 === 0)<img src="{{ $letterheadFooters[$pdfPageNumber] }}" alt="">@elseif($letterhead)&nbsp;@endif</div>
</section>
@endforeach
</body>
</html>
