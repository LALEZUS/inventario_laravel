<?php

namespace App\Http\Controllers;

use App\Models\CalendarReminder;
use App\Models\HardwareAsset;
use App\Models\Note;
use App\Models\Peripheral;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $computerBase = HardwareAsset::query();
        $calendarMonth = now()->startOfMonth();
        $calendarReminders = CalendarReminder::query()
            ->whereBetween('event_date', [$calendarMonth, $calendarMonth->copy()->endOfMonth()])
            ->orderBy('event_date')
            ->get()
            ->groupBy(fn (CalendarReminder $reminder) => $reminder->event_date->format('Y-m-d'));

        $alerts = collect();
        $maintenanceRows = DB::table('maintenance_logs')->where('asset_type', 'inventory')->whereNotNull('next_date')
            ->whereDate('next_date', '<=', now()->addDays(30))->orderBy('next_date')->limit(4)->get();
        $maintenanceAssets = HardwareAsset::whereIn('id', $maintenanceRows->pluck('asset_id')->filter()->all())
            ->get()->keyBy('id');
        $maintenanceRows->each(function ($maintenance) use ($alerts, $maintenanceAssets) {
                $computer = $maintenanceAssets->get($maintenance->asset_id);
                if (! $computer) return;
                $overdue = $maintenance->next_date < now()->format('Y-m-d');
                $alerts->push([
                    'icon'=>'bi-tools','tone'=>$overdue?'danger':'warning',
                    'title'=>$overdue?'Mantenimiento vencido':'Mantenimiento proximo',
                    'detail'=>$computer->name.' · '.\Carbon\Carbon::parse($maintenance->next_date)->format('d/m/Y'),
                    'url'=>route('computers.show',$computer).'#maintenance',
                ]);
            });
        DB::table('licenses')->whereNotNull('expiration_date')->where('expiration_date','<>','0000-00-00')
            ->whereDate('expiration_date','>=',now())->whereDate('expiration_date','<=',now()->addDays(45))
            ->orderBy('expiration_date')->limit(4)->get()->each(fn($license)=>$alerts->push([
                'icon'=>'bi-key','tone'=>'warning','title'=>'Licencia por vencer',
                'detail'=>$license->name.' · '.\Carbon\Carbon::parse($license->expiration_date)->format('d/m/Y'),
                'url'=>route('software-licenses.show',$license->id),
            ]));
        DB::table('inks')->whereColumn('quantity','<=','low_stock_threshold')->orderBy('quantity')->limit(3)->get()->each(fn($ink)=>$alerts->push([
            'icon'=>'bi-droplet','tone'=>$ink->quantity<1?'danger':'warning','title'=>'Tinta con existencia baja',
            'detail'=>trim($ink->brand.' '.$ink->model.' '.$ink->color).' · '.$ink->quantity.((int) $ink->quantity === 1 ? ' disponible' : ' disponibles'),'url'=>route('inks.show',$ink->id),
        ]));
        DB::table('toner')->whereColumn('quantity','<=','low_stock_threshold')->orderBy('quantity')->limit(3)->get()->each(fn($toner)=>$alerts->push([
            'icon'=>'bi-printer','tone'=>$toner->quantity<1?'danger':'warning','title'=>'Toner con existencia baja',
            'detail'=>trim($toner->brand.' '.$toner->model).' · '.$toner->quantity.' disponibles','url'=>route('toner.show',$toner->id),
        ]));
        HardwareAsset::query()->whereNull('employee_id')
            ->where(fn ($query) => $query->whereNull('assigned_user')->orWhereIn('assigned_user', ['', '-']))
            ->limit(3)->get()->each(fn (HardwareAsset $computer) => $alerts->push([
                'icon' => 'bi-person-exclamation', 'tone' => 'warning', 'title' => 'Equipo sin usuario',
                'detail' => $computer->name, 'url' => route('computers.show', $computer),
            ]));
        Peripheral::query()->whereNull('computer_id')->limit(2)->get()
            ->each(fn (Peripheral $peripheral) => $alerts->push([
                'icon' => 'bi-usb-symbol', 'tone' => 'warning', 'title' => 'Periferico sin computadora',
                'detail' => $peripheral->name, 'url' => route('peripherals.show', $peripheral),
            ]));

        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Buenos dias' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');

        return view('dashboard', [
            'greeting' => $greeting,
            'calendarMonth' => $calendarMonth,
            'calendarReminders' => $calendarReminders,
            'alerts' => $alerts->take(12),
            'recentNotes' => Note::query()->latest('updated_at')->limit(5)->get(),
            'stats' => [
                'computers' => (clone $computerBase)->count(),
                'assigned' => (clone $computerBase)->where(function ($query) {
                    $query->whereNotNull('employee_id')
                        ->orWhereNotNull('assigned_user');
                })->count(),
                'available' => (clone $computerBase)->where('status', 'Disponible')->count(),
                'maintenance' => (clone $computerBase)->where('status', 'like', '%mantenimiento%')->count(),
                'active_licenses' => DB::table('licenses')->where('status', 'like', 'Activa%')->count(),
                'cellphones' => DB::table('cellphones')->count(),
                'peripherals' => DB::table('peripherals')->count(),
                'printers' => DB::table('printers')->count(),
                'supply_units' => (int) DB::table('inks')->sum('quantity') + (int) DB::table('toner')->sum('quantity'),
                'network_devices' => DB::table('network_devices')->count(),
                'employees' => DB::table('employees')->count(),
                'credentials' => DB::table('account_management')->count()
                    + DB::table('correos_outlook')->count()
                    + DB::table('licenses')->count(),
                'emails' => DB::table('microsoft_emails')->count()
                    + DB::table('office_emails')->count(),
                'tutorials' => DB::table('tutorials')->count(),
                'files' => DB::table('ftp_catalog')->count(),
                'notes' => DB::table('notes')->count(),
                'reminders' => DB::table('calendar_reminders')->where('is_done',false)->count(),
            ],
            'recentComputers' => HardwareAsset::with('employee')->latest('updated_at')->limit(6)->get(),
        ]);
    }
}
