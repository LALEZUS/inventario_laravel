<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\InventoryDataset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryExportController extends Controller
{
    public function create(Request $request, string $dataset, InventoryDataset $datasets): View
    {
        $set = $datasets->get($dataset);
        $set['columns'] = $this->exportableColumns($request, $set);

        return view('exports.create', ['dataset' => $set]);
    }

    public function download(Request $request, string $dataset, InventoryDataset $datasets, AuditLogger $audit): StreamedResponse|RedirectResponse
    {
        $set = $datasets->get($dataset);
        $set['columns'] = $this->exportableColumns($request, $set);
        $defaultColumns = array_keys(array_diff_key($set['columns'], ['password' => true]));
        $selected = $request->input('columns', $defaultColumns);
        abort_unless(is_array($selected), 422);
        $selected = array_values(array_intersect(array_keys($set['columns']), $selected));
        if ($selected === []) {
            return back()->withErrors(['columns' => 'Selecciona al menos una columna.']);
        }

        $ids = $request->input('ids', []);
        if ($request->boolean('export_selected')) {
            $request->validate(['ids' => ['required', 'array', 'min:1', 'max:500'], 'ids.*' => ['integer']]);
        }
        $maxRows = max(1, min((int) config('inventory.export_max_rows', 10000), 50000));
        $rowsQuery = $datasets->query($dataset)->select(array_merge(['id'], $selected))->limit($maxRows);
        if ($request->boolean('export_selected')) $rowsQuery->whereIn('id', $ids);
        $rows = $rowsQuery->get();
        $headers = array_map(fn (string $column) => $set['columns'][$column], $selected);
        $audit->record('export', $set['table'], null, null, [
            'columns' => $selected,
            'rows' => $rows->count(),
            'selected' => $request->boolean('export_selected'),
            'contains_sensitive_values' => in_array('password', $selected, true),
        ]);
        $filename = $set['title'].'_'.now()->format('Y-m-d_His').'.xls';

        return response()->streamDownload(function () use ($headers, $rows, $selected, $set) {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#D9000D" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style></Styles>';
            echo '<Worksheet ss:Name="'.$this->xml(mb_substr($set['title'], 0, 31)).'"><Table>';
            foreach ($headers as $header) {
                echo '<Column ss:AutoFitWidth="0" ss:Width="160"/>';
            }
            echo '<Row>';
            foreach ($headers as $header) {
                echo '<Cell ss:StyleID="Header"><Data ss:Type="String">'.$this->xml($header).'</Data></Cell>';
            }
            echo '</Row>';

            foreach ($rows as $row) {
                echo '<Row>';
                foreach ($selected as $column) {
                    $value = $row->{$column};
                    if (is_bool($value) || in_array($column, ['is_network', 'is_done', 'is_archived'], true)) {
                        $value = $value ? 'Si' : 'No';
                    }
                    echo '<Cell><Data ss:Type="String">'.$this->xml((string) ($value ?? '')).'</Data></Cell>';
                }
                echo '</Row>';
            }
            echo '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>1</SplitHorizontal><TopRowBottomPane>1</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet></Workbook>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function xml(string $value): string
    {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function exportableColumns(Request $request, array $set): array
    {
        $columns = $set['columns'];

        if ($set['key'] === 'account-credentials' && $request->user()?->role === 'admin') {
            $columns['password'] = 'Contraseña';
        }

        return $columns;
    }
}
