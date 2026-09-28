<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryDataset
{
    private const DATASETS = [
        'computers' => ['table'=>'hardware_assets','title'=>'Computadoras','display'=>'name','columns'=>[
            'code'=>'Folio','name'=>'Equipo','format'=>'Formato','brand'=>'Marca','model'=>'Modelo','serial'=>'Serie',
            'processor'=>'Procesador','ram'=>'RAM','storage'=>'Almacenamiento','os'=>'Sistema operativo','os_version'=>'Version SO',
            'architecture'=>'Arquitectura','bios'=>'BIOS / UEFI','motherboard'=>'Placa base','gpu'=>'Graficos','network_adapter'=>'Adaptador de red',
            'mac_address'=>'MAC','secure_boot'=>'Arranque seguro','tpm'=>'TPM','anydesk_id'=>'ID de AnyDesk','rustdesk_id'=>'ID de RustDesk',
            'value'=>'Valor','status'=>'Estado','location'=>'Ubicacion','zone'=>'Zona',
            'assigned_user'=>'Usuario asignado','delivery_date'=>'Fecha de entrega','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['DISPONIBLE','ENTREGADO','MANTENIMIENTO','BAJA'],'location'=>'zone']],
        'cellphones' => ['table'=>'cellphones','title'=>'Celulares','display'=>'model','columns'=>[
            'employee_name_legacy'=>'Empleado','model'=>'Modelo','area'=>'Area','email_account'=>'Correo','recovery_account'=>'Correo de recuperacion',
            'phone_number'=>'Telefono','birth_date'=>'Fecha de nacimiento','status'=>'Estado','update_note'=>'Actualizacion','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['En Uso','Disponible','Mantenimiento','Baja'],'location'=>'area']],
        'peripherals' => ['table'=>'peripherals','title'=>'Perifericos','display'=>'name','columns'=>[
            'code'=>'Folio','name'=>'Articulo','category'=>'Categoria','brand'=>'Marca','model'=>'Modelo','serial'=>'Serie','quantity'=>'Cantidad',
            'status'=>'Estado','location'=>'Ubicacion','assigned_to'=>'Asignado a','computer_id'=>'ID computadora','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Disponible','Asignado','Nuevo','Usado','Mantenimiento','Baja'],'location'=>'location']],
        'employees' => ['table'=>'employees','title'=>'Empleados','display'=>'full_name','columns'=>[
            'full_name'=>'Nombre','department'=>'Area','position'=>'Puesto','email_corporate'=>'Correo corporativo','extension'=>'Extension',
            'status'=>'Estado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Activo','Inactivo'],'location'=>'department']],
        'printers' => ['table'=>'printers','title'=>'Impresoras','display'=>'name','columns'=>[
            'code'=>'Folio','name'=>'Impresora','brand'=>'Marca','model'=>'Modelo','serial'=>'Serie','ip_address'=>'IP','zone'=>'Zona',
            'assigned_to'=>'Asignada a','is_network'=>'Es de red','supply_type'=>'Tipo de consumible','ink_type'=>'Consumible','status'=>'Estado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Activo','Disponible','En servicio','Mantenimiento','Baja'],'location'=>'zone']],
        'inks' => ['table'=>'inks','title'=>'Tintas','display'=>'model','columns'=>[
            'brand'=>'Marca','model'=>'Modelo','color'=>'Color','type'=>'Tipo','capacity'=>'Capacidad','quantity'=>'Cantidad','purchase_date'=>'Compra',
            'expiry_date'=>'Vencimiento','status'=>'Estado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Disponible','Completo','Bajo','Agotado','Vencido']]],
        'toner' => ['table'=>'toner','title'=>'Toner','display'=>'model','columns'=>[
            'brand'=>'Marca','model'=>'Modelo','quantity'=>'Cantidad','status'=>'Estado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['NUEVO','DISPONIBLE','BAJO','AGOTADO']]],
        'network-devices' => ['table'=>'network_devices','title'=>'Dispositivos de red','display'=>'device_name','columns'=>[
            'device_name'=>'Nombre','device_type'=>'Tipo','brand'=>'Marca','ip_address'=>'IP','mac_address'=>'MAC','location'=>'Ubicacion',
            'status'=>'Estado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Activo','Inactivo','Mantenimiento','Desconocido'],'location'=>'location']],
        'watchguard' => ['table'=>'watchguard_users','title'=>'Usuarios WatchGuard','display'=>'username','columns'=>[
            'username'=>'Usuario','assigned_to'=>'Asignado a','area'=>'Area','ip'=>'IP','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado']],
        'enterprise-networks' => ['table'=>'enterprise_networks','title'=>'Redes empresariales','display'=>'network_name','columns'=>[
            'network_name'=>'Red','vlan'=>'VLAN / SSID','location'=>'Ubicacion','encryption'=>'Cifrado','comments'=>'Notas','notes_extra'=>'Notas adicionales','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['location'=>'location']],
        'account-credentials' => ['table'=>'account_management','title'=>'Credenciales','display'=>'email','columns'=>[
            'email'=>'Cuenta','account_type'=>'Tipo','assigned_to'=>'Asignada a','status'=>'Estado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Activa','Inactiva','Baja']]],
        'outlook-accounts' => ['table'=>'correos_outlook','title'=>'Cuentas Outlook','display'=>'correo','columns'=>[
            'correo'=>'Correo','estatus'=>'Estado','servidor_entrada'=>'Servidor entrada','puerto_entrada'=>'Puerto entrada','ssl_entrada'=>'SSL entrada',
            'servidor_salida'=>'Servidor salida','puerto_salida'=>'Puerto salida','cifrado_salida'=>'Cifrado salida','comentarios'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado']],
        'licenses' => ['table'=>'licenses','title'=>'Licencias','display'=>'name','columns'=>[
            'name'=>'Licencia','type'=>'Tipo','vendor'=>'Proveedor','expiration_date'=>'Vencimiento','status'=>'Estado','link'=>'Enlace','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Activa','Por vencer','Vencida','Baja']]],
        'microsoft-emails' => ['table'=>'microsoft_emails','title'=>'Correos Microsoft 365','display'=>'email','columns'=>[
            'email'=>'Correo','status'=>'Estado','activation_date'=>'Activacion','renewal_date'=>'Renovacion','admin_url'=>'URL administracion','admin_account'=>'Cuenta administradora','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Activa','Inactiva','Baja']]],
        'office-emails' => ['table'=>'office_emails','title'=>'Correos Windows','display'=>'email','columns'=>[
            'email'=>'Correo','status'=>'Estado','activation_date'=>'Activacion','renewal_date'=>'Renovacion','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado'],
            'bulk'=>['status'=>['Activa','Inactiva','Baja']]],
        'email-backups' => ['table'=>'email_backups','title'=>'Reenvios de correo','display'=>'original_email','columns'=>[
            'original_name'=>'Persona original','original_email'=>'Correo original','backup_name'=>'Persona de respaldo','backup_email'=>'Correo destino',
            'start_date'=>'Inicio','end_date'=>'Fin','is_done'=>'Completado','is_archived'=>'Archivado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado']],
        'tutorials' => ['table'=>'tutorials','title'=>'Tutoriales','display'=>'title','columns'=>[
            'title'=>'Titulo','category'=>'Categoria','description'=>'Descripcion','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado']],
        'gallery' => ['table'=>'gallery','title'=>'Galeria','display'=>'title','columns'=>['title'=>'Titulo','notes'=>'Notas','upload_date'=>'Fecha de carga']],
        'files' => ['table'=>'ftp_catalog','title'=>'Archivos generales','display'=>'alias_name','columns'=>[
            'original_name'=>'Nombre original','alias_name'=>'Alias','file_size'=>'Tamano','upload_date'=>'Fecha de carga','uploaded_by'=>'Subido por','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado']],
        'notes' => ['table'=>'notes','title'=>'Notas','display'=>'title','columns'=>['title'=>'Titulo','content'=>'Contenido','created_at'=>'Creado','updated_at'=>'Actualizado']],
        'calendar' => ['table'=>'calendar_reminders','title'=>'Agenda','display'=>'title','columns'=>[
            'event_date'=>'Fecha','title'=>'Titulo','description'=>'Descripcion','is_done'=>'Completado','comments'=>'Notas','created_at'=>'Creado','updated_at'=>'Actualizado']],
    ];

    public function get(string $key): array
    {
        if (! isset(self::DATASETS[$key])) throw ValidationException::withMessages(['dataset' => 'El conjunto solicitado no existe.']);
        return ['key'=>$key] + self::DATASETS[$key];
    }

    public function query(string $key): Builder
    {
        $set = $this->get($key);
        return DB::table($set['table'])->orderBy($set['display']);
    }

    public function forRequest(Request $request): ?array
    {
        $name = (string) $request->route()?->getName();
        $key = match (true) {
            str_starts_with($name, 'computers.') => 'computers', str_starts_with($name, 'cellphones.') => 'cellphones',
            str_starts_with($name, 'peripherals.') => 'peripherals', str_starts_with($name, 'employees.') => 'employees',
            str_starts_with($name, 'printers.') => 'printers', str_starts_with($name, 'inks.') => 'inks', str_starts_with($name, 'toner.') => 'toner',
            $name === 'supplies.index' => $request->query('type') === 'toner' ? 'toner' : 'inks',
            str_starts_with($name, 'network-devices.') => 'network-devices', str_starts_with($name, 'watchguard-users.') => 'watchguard',
            str_starts_with($name, 'enterprise-networks.') => 'enterprise-networks',
            $name === 'network.index' => match ($request->query('type')) {'watchguard'=>'watchguard','enterprise'=>'enterprise-networks',default=>'network-devices'},
            str_starts_with($name, 'account-credentials.') => 'account-credentials', str_starts_with($name, 'outlook-accounts.') => 'outlook-accounts',
            str_starts_with($name, 'software-licenses.') => 'licenses',
            $name === 'credentials.index' => match ($request->query('type')) {'outlook'=>'outlook-accounts','licenses'=>'licenses',default=>'account-credentials'},
            str_starts_with($name, 'microsoft-emails.') => 'microsoft-emails', str_starts_with($name, 'office-emails.') => 'office-emails',
            str_starts_with($name, 'email-backups.') => 'email-backups',
            $name === 'emails.index' => match ($request->query('type')) {'windows'=>'office-emails','backups'=>'email-backups',default=>'microsoft-emails'},
            str_starts_with($name, 'tutorials.') => 'tutorials', str_starts_with($name, 'gallery.') => 'gallery', str_starts_with($name, 'files.') => 'files',
            str_starts_with($name, 'notes.') => 'notes', str_starts_with($name, 'calendar.') => 'calendar', default => null,
        };
        return $key ? $this->get($key) : null;
    }
}
