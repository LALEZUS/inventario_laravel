<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'department' => $this->department,
            'position' => $this->position,
            'email_corporate' => $this->email_corporate,
            'extension' => $this->extension,
            'status' => $this->status,
            'comments' => $this->comments,
            'related_assets' => $this->when(
                $this->relationLoaded('hardwareAssets'),
                fn () => collect()
                    ->concat($this->hardwareAssets->map(fn ($item) => [
                        'id' => $item->id,
                        'type' => 'computer',
                        'badge' => 'PC',
                        'title' => $item->name ?: 'Computadora',
                        'subtitle' => ($item->code ?: 'Sin folio').' · '.($item->status ?: 'Sin estado'),
                        'status' => $item->status,
                    ]))
                    ->concat($this->cellphones->map(fn ($item) => [
                        'id' => $item->id,
                        'type' => 'cellphone',
                        'badge' => 'TEL',
                        'title' => $item->model ?: 'Celular',
                        'subtitle' => ($item->phone_number ?: 'Sin línea').' · '.($item->status ?: 'Sin estado'),
                        'status' => $item->status,
                    ]))
                    ->concat($this->peripherals->map(fn ($item) => [
                        'id' => $item->id,
                        'type' => 'peripheral',
                        'badge' => 'PER',
                        'title' => $item->name ?: 'Periférico',
                        'subtitle' => ($item->code ?: 'Sin folio').' · '.($item->status ?: 'Sin estado'),
                        'status' => $item->status,
                    ]))
                    ->concat($this->printers->map(fn ($item) => [
                        'id' => $item->id,
                        'type' => 'printer',
                        'badge' => 'IMP',
                        'title' => $item->name ?: 'Impresora',
                        'subtitle' => ($item->ip_address ?: 'Conexión local').' · '.($item->status ?: 'Sin estado'),
                        'status' => $item->status,
                    ]))
                    ->concat($this->outlookAccounts->map(fn ($item) => [
                        'id' => $item->id,
                        'type' => 'outlook',
                        'badge' => 'MAIL',
                        'title' => $item->correo ?: 'Correo Outlook',
                        'subtitle' => 'Outlook · '.($item->estatus ?: 'Sin estado'),
                        'status' => $item->estatus,
                    ]))
                    ->values()
                    ->all()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
