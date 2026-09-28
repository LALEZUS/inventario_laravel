<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AccountCredential;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_export_uses_selected_safe_columns(): void
    {
        $user = $this->user('soporte');
        HardwareAsset::create(['name'=>'Equipo Excel','status'=>'DISPONIBLE','admin_password'=>'secreto']);

        $response = $this->actingAs($user)->post(route('inventory-export.download','computers'), [
            'columns'=>['name','status'],
        ]);

        $response->assertOk()->assertHeader('content-disposition');
        $this->assertStringContainsString('.xls', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('<Workbook', $content);
        $this->assertStringContainsString('Equipo Excel', $content);
        $this->assertStringNotContainsString('secreto', $content);
        $this->assertDatabaseHas('audit_logs',['action'=>'export','entity'=>'hardware_assets']);
    }

    public function test_only_admin_can_export_account_passwords_when_explicitly_selected(): void
    {
        $credential = AccountCredential::create([
            'email' => 'excel@example.test',
            'account_type' => 'Personal',
            'password' => '=Clave<&>2026',
            'status' => 'Activa',
        ]);

        $admin = $this->user('admin', 'export_password_admin');
        $this->actingAs($admin)->get(route('inventory-export.create', 'account-credentials'))
            ->assertOk()
            ->assertSee('value="password"', false)
            ->assertSee('información confidencial');

        $adminResponse = $this->actingAs($admin)->post(route('inventory-export.download', 'account-credentials'), [
            'columns' => ['email', 'password'],
        ])->assertOk();
        $adminContent = $adminResponse->streamedContent();
        $this->assertStringContainsString('<Data ss:Type="String">Contraseña</Data>', $adminContent);
        $this->assertStringContainsString('<Data ss:Type="String">=Clave&lt;&amp;&gt;2026</Data>', $adminContent);

        $support = $this->user('soporte', 'export_password_support');
        $this->actingAs($support)->get(route('inventory-export.create', 'account-credentials'))
            ->assertOk()
            ->assertDontSee('value="password"', false);

        $supportResponse = $this->actingAs($support)->post(route('inventory-export.download', 'account-credentials'), [
            'columns' => ['email', 'password'],
        ])->assertOk();
        $supportContent = $supportResponse->streamedContent();
        $this->assertStringContainsString($credential->email, $supportContent);
        $this->assertStringNotContainsString('=Clave', $supportContent);
    }

    public function test_support_can_update_multiple_records_and_consultation_cannot(): void
    {
        $support = $this->user('soporte','bulk_support');
        $first = HardwareAsset::create(['name'=>'Uno','status'=>'DISPONIBLE']);
        $second = HardwareAsset::create(['name'=>'Dos','status'=>'DISPONIBLE']);

        $this->actingAs($support)->get(route('bulk.index', 'computers'))
            ->assertOk()
            ->assertSee('Gestion masiva')
            ->assertSee('Uno');

        $this->actingAs($support)->put(route('bulk.update','computers'), [
            'ids'=>[$first->id,$second->id],'action'=>'status','value'=>'MANTENIMIENTO',
        ])->assertSessionHas('success');
        $this->assertSame(2, HardwareAsset::where('status','MANTENIMIENTO')->count());

        $consultation = $this->user('consulta','bulk_readonly');
        $this->actingAs($consultation)->put(route('bulk.update','computers'), [
            'ids'=>[$first->id],'action'=>'status','value'=>'BAJA',
        ])->assertForbidden();
    }

    public function test_assignment_updates_computer_and_return_releases_it(): void
    {
        $user = $this->user('soporte','assignment_support');
        $employee = Employee::create(['full_name'=>'Persona Asignada','status'=>'Activo']);
        $computer = HardwareAsset::create(['name'=>'Equipo asignable','status'=>'DISPONIBLE']);

        $this->actingAs($user)->post(route('computers.assignments.store',$computer), [
            'employee_id'=>$employee->id,'date_assigned'=>'2026-08-17','condition_on_assign'=>'Bueno',
        ])->assertRedirect(route('computers.show',$computer));
        $assignment = Assignment::firstOrFail();
        $this->assertDatabaseHas('hardware_assets',['id'=>$computer->id,'employee_id'=>$employee->id,'status'=>'ENTREGADO']);

        $this->actingAs($user)->put(route('computers.assignments.return',[$computer,$assignment]), [
            'date_returned'=>'2026-08-18','condition_on_return'=>'Bueno',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('hardware_assets',['id'=>$computer->id,'employee_id'=>null,'status'=>'DISPONIBLE']);
    }

    public function test_deleting_an_active_assignment_releases_the_computer(): void
    {
        $admin = $this->user('admin', 'assignment_admin');
        $employee = Employee::create(['full_name' => 'Custodio temporal', 'status' => 'Activo']);
        $computer = HardwareAsset::create([
            'name' => 'Equipo con asignacion', 'status' => 'ENTREGADO',
            'employee_id' => $employee->id, 'assigned_user' => $employee->full_name,
        ]);
        $assignment = Assignment::create([
            'asset_type' => 'inventory', 'asset_id' => $computer->id,
            'employee_id' => $employee->id, 'assigned_to' => $employee->full_name,
            'date_assigned' => '2026-08-17',
        ]);

        $this->actingAs($admin)->delete(route('computers.assignments.destroy', [$computer, $assignment]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
        $this->assertDatabaseHas('hardware_assets', [
            'id' => $computer->id, 'employee_id' => null,
            'assigned_user' => null, 'status' => 'DISPONIBLE',
        ]);
    }

    public function test_maintenance_is_recorded_and_appears_as_dashboard_alert(): void
    {
        $user = $this->user('soporte','maintenance_support');
        $computer = HardwareAsset::create(['name'=>'Equipo mantenimiento','status'=>'MANTENIMIENTO']);
        $this->actingAs($user)->post(route('computers.maintenance.store',$computer), [
            'date'=>'2026-08-17','description'=>'Limpieza interna','category'=>'Preventivo',
            'status'=>'Completado','next_date'=>now()->addDays(10)->format('Y-m-d'),'cost'=>450,
        ])->assertSessionHas('success');
        $this->assertSame(1, MaintenanceLog::count());
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Mantenimiento proximo');
    }

    private function user(string $role, ?string $username = null): User
    {
        return User::create(['username'=>$username ?: 'operations_'.$role,'password'=>Hash::make('password'),'full_name'=>'Usuario Operaciones','role'=>$role]);
    }
}
