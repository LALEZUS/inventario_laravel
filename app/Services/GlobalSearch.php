<?php

namespace App\Services;

use App\Models\AccountCredential;
use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\EmailBackup;
use App\Models\FileCatalog;
use App\Models\GalleryItem;
use App\Models\EnterpriseNetwork;
use App\Models\HardwareAsset;
use App\Models\Ink;
use App\Models\MicrosoftEmail;
use App\Models\Note;
use App\Models\NetworkDevice;
use App\Models\OutlookAccount;
use App\Models\OfficeEmail;
use App\Models\CalendarReminder;
use App\Models\Peripheral;
use App\Models\Printer;
use App\Models\SoftwareLicense;
use App\Models\Toner;
use App\Models\Tutorial;
use App\Models\WatchguardUser;
use Illuminate\Support\Collection;

class GlobalSearch
{
    public function search(string $query, int $limit = 40, bool $balanceTypes = true): Collection
    {
        $query = trim(mb_substr($query, 0, 120));
        if (mb_strlen($query) < 2) return collect();
        $limit = min(100, max(1, $limit));
        $terms = collect(preg_split('/\s+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY))->take(6)->all();
        // Suggestions reserve space for different modules. The complete search
        // must not discard matches just because many belong to the same table.
        $perType = $balanceTypes
            ? min(20, max(3, (int) ceil($limit / 21)))
            : $limit;

        return collect()
            ->concat($this->computers($terms, $perType))
            ->concat($this->cellphones($terms, $perType))
            ->concat($this->peripherals($terms, $perType))
            ->concat($this->printers($terms, $perType))
            ->concat($this->inks($terms, $perType))
            ->concat($this->toners($terms, $perType))
            ->concat($this->networkDevices($terms, $perType))
            ->concat($this->watchguardUsers($terms, $perType))
            ->concat($this->enterpriseNetworks($terms, $perType))
            ->concat($this->accountCredentials($terms, $perType))
            ->concat($this->outlookAccounts($terms, $perType))
            ->concat($this->softwareLicenses($terms, $perType))
            ->concat($this->microsoftEmails($terms, $perType))
            ->concat($this->officeEmails($terms, $perType))
            ->concat($this->emailBackups($terms, $perType))
            ->concat($this->tutorials($terms, $perType))
            ->concat($this->gallery($terms, $perType))
            ->concat($this->files($terms, $perType))
            ->concat($this->notes($terms, $perType))
            ->concat($this->reminders($terms, $perType))
            ->concat($this->employees($terms, $perType))
            ->map(fn (array $result) => $result + ['score' => $this->score($query, $result)])
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function computers(array $terms, int $limit): Collection
    {
        $columns = ['name', 'brand', 'model', 'serial', 'code', 'assigned_user', 'zone', 'location', 'processor', 'mac_address', 'anydesk_id', 'rustdesk_id', 'value', 'status'];
        return $this->applyTerms(HardwareAsset::with('employee'), $terms, $columns)
            ->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
                'id' => $item->id, 'status' => $item->status, 'type' => 'Computadora', 'icon' => 'PC', 'title' => $item->name,
                'subtitle' => trim(($item->brand ?: 'Sin marca').' Â· '.($item->model ?: 'Sin modelo')),
                'meta' => implode(' Â· ', array_filter([
                    $item->assigned_to,
                    $item->code,
                    $item->serial,
                    $item->anydesk_id ? 'AnyDesk '.$item->anydesk_id : null,
                    $item->rustdesk_id ? 'RustDesk '.$item->rustdesk_id : null,
                    $item->value !== null ? 'Valor '.$item->value : null,
                    $item->zone ?: $item->location,
                ])),
                'url' => route('computers.show', $item),
            ]);
    }

    private function cellphones(array $terms, int $limit): Collection
    {
        $columns = ['employee_name_legacy', 'model', 'area', 'email_account', 'recovery_account', 'phone_number', 'status', 'comments'];
        return $this->applyTerms(Cellphone::with('employee'), $terms, $columns)
            ->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
                'id' => $item->id, 'status' => $item->status, 'type' => 'Celular', 'icon' => 'TEL', 'title' => $item->model ?: 'Celular sin modelo',
                'subtitle' => $item->assigned_name ?: 'Sin empleado asignado',
                'meta' => implode(' Â· ', array_filter([$item->phone_number, $item->email_account, $item->area, $item->status])),
                'url' => route('cellphones.show', $item),
            ]);
    }

    private function peripherals(array $terms, int $limit): Collection
    {
        $columns = ['name', 'brand', 'model', 'serial', 'code', 'category', 'assigned_to', 'location', 'status'];
        return $this->applyTerms(Peripheral::with(['employee', 'computer']), $terms, $columns)
            ->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
                'type' => 'Periferico', 'icon' => 'PER', 'title' => $item->name,
                'subtitle' => trim(($item->brand ?: 'Sin marca').' Â· '.($item->model ?: 'Sin modelo')),
                'meta' => implode(' Â· ', array_filter([$item->assigned_name, $item->computer?->name, $item->code, $item->serial])),
                'url' => route('peripherals.show', $item),
            ]);
    }

    private function employees(array $terms, int $limit): Collection
    {
        $columns = ['full_name', 'department', 'position', 'email_corporate', 'extension', 'status'];
        return $this->applyTerms(Employee::query(), $terms, $columns)
            ->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
                'id' => $item->id, 'status' => $item->status, 'type' => 'Empleado', 'icon' => 'EMP', 'title' => $item->full_name,
                'subtitle' => implode(' Â· ', array_filter([$item->department, $item->position])),
                'meta' => implode(' Â· ', array_filter([$item->email_corporate, $item->extension ? 'Ext. '.$item->extension : null, $item->status])),
                'url' => route('employees.show', $item),
            ]);
    }

    private function printers(array $terms, int $limit): Collection
    {
        $columns = ['name', 'brand', 'model', 'serial', 'code', 'ip_address', 'zone', 'assigned_to', 'supply_type', 'ink_type', 'status'];

        return $this->applyTerms(Printer::with('employee'), $terms, $columns)
            ->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
                'type' => 'Impresora', 'icon' => 'IMP', 'title' => $item->name,
                'subtitle' => trim(($item->brand ?: 'Sin marca').' - '.($item->model ?: 'Sin modelo')),
                'meta' => implode(' - ', array_filter([$item->assigned_name, $item->ip_address, $item->zone, $item->ink_type, $item->status])),
                'url' => route('printers.show', $item),
            ]);
    }

    private function inks(array $terms, int $limit): Collection
    {
        $columns = ['brand', 'model', 'color', 'type', 'capacity', 'status', 'comments'];

        return $this->applyTerms(Ink::query(), $terms, $columns)
            ->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
                'type' => 'Tinta', 'icon' => 'INK', 'title' => trim($item->brand.' '.$item->type),
                'subtitle' => implode(' - ', array_filter([$item->color, $item->capacity, $item->model])),
                'meta' => $item->quantity.' unidades - '.$item->status,
                'url' => route('inks.show', $item),
            ]);
    }

    private function toners(array $terms, int $limit): Collection
    {
        $columns = ['brand', 'model', 'status', 'comentarios', 'comments'];

        return $this->applyTerms(Toner::query(), $terms, $columns)
            ->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
                'type' => 'Toner', 'icon' => 'TON', 'title' => trim($item->brand.' '.$item->model),
                'subtitle' => $item->notes ?: 'Sin comentarios',
                'meta' => $item->quantity.' unidades - '.$item->status,
                'url' => route('toner.show', $item),
            ]);
    }

    private function networkDevices(array $terms, int $limit): Collection
    {
        $columns = ['device_name', 'device_type', 'ip_address', 'mac_address', 'location', 'brand', 'status', 'comments'];
        return $this->applyTerms(NetworkDevice::query(), $terms, $columns)->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
            'type' => 'Dispositivo de red', 'icon' => 'NET', 'title' => $item->device_name,
            'subtitle' => implode(' - ', array_filter([$item->device_type, $item->brand])),
            'meta' => implode(' - ', array_filter([$item->ip_address, $item->mac_address, $item->location, $item->status])),
            'url' => route('network-devices.show', $item),
        ]);
    }

    private function watchguardUsers(array $terms, int $limit): Collection
    {
        $columns = ['username', 'assigned_to', 'area', 'ip', 'comments'];
        return $this->applyTerms(WatchguardUser::query(), $terms, $columns)->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
            'type' => 'WatchGuard', 'icon' => 'WG', 'title' => $item->username,
            'subtitle' => $item->assigned_to ?: 'Sin asignar',
            'meta' => implode(' - ', array_filter([$item->area, $item->ip])),
            'url' => route('watchguard-users.show', $item),
        ]);
    }

    private function enterpriseNetworks(array $terms, int $limit): Collection
    {
        $columns = ['network_name', 'vlan', 'location', 'encryption', 'comments', 'notes_extra'];
        return $this->applyTerms(EnterpriseNetwork::query(), $terms, $columns)->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
            'type' => 'Red empresarial', 'icon' => 'WIFI', 'title' => $item->network_name,
            'subtitle' => $item->location ?: 'Sin ubicacion',
            'meta' => implode(' - ', array_filter([$item->vlan ? 'VLAN '.$item->vlan : null, $item->encryption])),
            'url' => route('enterprise-networks.show', $item),
        ]);
    }

    private function accountCredentials(array $terms, int $limit): Collection
    {
        $columns = ['email', 'account_type', 'assigned_to', 'status', 'comments'];
        return $this->applyTerms(AccountCredential::with('employee'), $terms, $columns)->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
            'type' => 'Cuenta corporativa', 'icon' => 'ACC', 'title' => $item->email,
            'subtitle' => $item->account_type,
            'meta' => implode(' - ', array_filter([$item->employee?->full_name ?: $item->assigned_to, $item->status])),
            'url' => route('account-credentials.show', $item),
        ]);
    }

    private function outlookAccounts(array $terms, int $limit): Collection
    {
        $columns = ['correo', 'estatus', 'comentarios', 'servidor_entrada', 'servidor_salida', 'cifrado_salida'];
        return $this->applyTerms(OutlookAccount::query(), $terms, $columns)->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
            'type' => 'Correo Outlook', 'icon' => 'MAIL', 'title' => $item->correo,
            'subtitle' => $item->estatus,
            'meta' => implode(' - ', array_filter([$item->servidor_entrada, $item->servidor_salida])),
            'url' => route('outlook-accounts.show', $item),
        ]);
    }

    private function softwareLicenses(array $terms, int $limit): Collection
    {
        $columns = ['name', 'type', 'status', 'vendor', 'link', 'comments'];
        return $this->applyTerms(SoftwareLicense::query(), $terms, $columns)->latest('updated_at')->limit($limit)->get()->map(fn ($item) => [
            'type' => 'Licencia', 'icon' => 'KEY', 'title' => $item->name,
            'subtitle' => implode(' - ', array_filter([$item->vendor, $item->type])),
            'meta' => implode(' - ', array_filter([$item->status, $item->expiration_date?->format('d/m/Y')])),
            'url' => route('software-licenses.show', $item),
        ]);
    }

    private function microsoftEmails(array $terms, int $limit): Collection
    {
        $columns=['email','status','activation_date','renewal_date','admin_url','admin_account','comments'];
        return $this->applyTerms(MicrosoftEmail::query(),$terms,$columns)->latest('updated_at')->limit($limit)->get()->map(fn($item)=>['type'=>'Microsoft 365','icon'=>'365','title'=>$item->email,'subtitle'=>$item->status,'meta'=>implode(' - ',array_filter([$item->admin_account,$item->dateLabel('renewal_date')])),'url'=>route('microsoft-emails.show',$item)]);
    }

    private function officeEmails(array $terms, int $limit): Collection
    {
        $columns=['email','status','activation_date','renewal_date','comments'];
        return $this->applyTerms(OfficeEmail::query(),$terms,$columns)->latest('updated_at')->limit($limit)->get()->map(fn($item)=>['type'=>'Correo Windows','icon'=>'WIN','title'=>$item->email,'subtitle'=>$item->status,'meta'=>$item->dateLabel('renewal_date') ?: 'Sin renovacion','url'=>route('office-emails.show',$item)]);
    }

    private function emailBackups(array $terms, int $limit): Collection
    {
        $columns=['original_name','original_email','backup_name','backup_email','comments'];
        return $this->applyTerms(EmailBackup::query(),$terms,$columns)->latest('updated_at')->limit($limit)->get()->map(fn($item)=>['type'=>'Reenvio de correo','icon'=>'FWD','title'=>$item->original_email,'subtitle'=>'Destino: '.$item->backup_email,'meta'=>$item->is_archived?'Archivado':($item->is_done?'Hecho':'Pendiente'),'url'=>route('email-backups.show',$item)]);
    }

    private function tutorials(array $terms,int $limit):Collection { return $this->applyTerms(Tutorial::query(),$terms,['title','category','description','comments'])->latest()->limit($limit)->get()->map(fn($x)=>['type'=>'Tutorial','icon'=>'PDF','title'=>$x->title,'subtitle'=>$x->category?:'Tutorial','meta'=>$x->description?:'Sin descripcion','url'=>route('tutorials.show',$x)]); }
    private function gallery(array $terms,int $limit):Collection { return $this->applyTerms(GalleryItem::query(),$terms,['title','notes'])->latest('upload_date')->limit($limit)->get()->map(fn($x)=>['type'=>'Galeria','icon'=>'IMG','title'=>$x->title,'subtitle'=>'Imagen','meta'=>$x->notes?:'Sin notas','url'=>route('gallery.show',$x)]); }
    private function files(array $terms,int $limit):Collection { return $this->applyTerms(FileCatalog::query(),$terms,['original_name','alias_name','comments'])->latest('upload_date')->limit($limit)->get()->map(fn($x)=>['type'=>'Archivo','icon'=>'FILE','title'=>$x->alias_name?:$x->original_name,'subtitle'=>$x->original_name,'meta'=>number_format(($x->file_size??0)/1048576,2).' MB','url'=>route('files.show',$x)]); }
    private function notes(array $terms,int $limit):Collection { return $this->applyTerms(Note::query(),$terms,['title','content'])->latest()->limit($limit)->get()->map(fn($x)=>['type'=>'Nota','icon'=>'NOTE','title'=>$x->title,'subtitle'=>'Nota interna','meta'=>\Illuminate\Support\Str::limit($x->content,100),'url'=>route('notes.show',$x)]); }
    private function reminders(array $terms,int $limit):Collection { return $this->applyTerms(CalendarReminder::query(),$terms,['title','description','comments'])->latest('event_date')->limit($limit)->get()->map(fn($x)=>['type'=>'Recordatorio','icon'=>'CAL','title'=>$x->title,'subtitle'=>$x->event_date->format('d/m/Y'),'meta'=>$x->is_done?'Completado':'Pendiente','url'=>route('calendar.index',['month'=>$x->event_date->format('Y-m')])]); }

    private function applyTerms($builder, array $terms, array $columns)
    {
        foreach ($terms as $term) {
            $builder->where(function ($query) use ($columns, $term) {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}($column, 'like', '%'.$term.'%');
                }
            });
        }
        return $builder;
    }

    private function score(string $query, array $result): int
    {
        $needle = mb_strtolower($query);
        $title = mb_strtolower((string) $result['title']);
        $haystack = mb_strtolower(implode(' ', [$result['title'], $result['subtitle'], $result['meta']]));
        if ($title === $needle) return 1000;
        if (str_starts_with($title, $needle)) return 700;
        if (str_contains($title, $needle)) return 500;
        return 200 + substr_count($haystack, $needle) * 10;
    }
}

