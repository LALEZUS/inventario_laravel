<?php

namespace Tests\Feature;

use App\Models\AccountCredential;
use App\Models\AuditLog;
use App\Models\OutlookAccount;
use App\Models\SoftwareLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CredentialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_account_and_find_it_immediately(): void
    {
        $response = $this->actingAs($this->user('soporte'))->post(route('account-credentials.store'), [
            'email' => 'inventario.zenith@example.test',
            'password' => 'Account-Secret-2026',
            'account_type' => 'Microsoft 365',
            'assigned_to' => 'Mesa de ayuda',
            'status' => 'Activo',
        ]);

        $credential = AccountCredential::firstOrFail();
        $response->assertSessionHasNoErrors()->assertRedirect(route('account-credentials.show', $credential));
        $this->get(route('credentials.index', ['search' => 'zenith']))->assertOk()->assertSee('inventario.zenith@example.test');
        $this->getJson(route('search.suggestions', ['q' => 'zenith']))
            ->assertOk()->assertJsonPath('results.0.url', route('account-credentials.show', $credential));
        $this->assertSame('Account-Secret-2026', AuditLog::where('entity', 'account_management')->firstOrFail()->after_data['password']);
    }

    public function test_credential_forms_use_catalog_selects_and_keep_legacy_statuses(): void
    {
        $support = $this->user('soporte');
        $license = SoftwareLicense::create([
            'name' => 'Licencia historica',
            'type' => 'Tipo legado',
            'status' => 'Archivada manualmente',
        ]);
        $account = AccountCredential::create([
            'email' => 'legacy-status@example.test',
            'account_type' => 'Personal',
            'status' => 'perdida',
        ]);

        $this->actingAs($support)
            ->get(route('software-licenses.edit', $license))
            ->assertOk()
            ->assertSee('<select name="type">', false)
            ->assertSee('<select name="status" required>', false)
            ->assertSee('value="Tipo legado" selected', false)
            ->assertSee('value="Archivada manualmente" selected', false);

        $this->get(route('account-credentials.edit', $account))
            ->assertOk()
            ->assertSee('<select name="status" required>', false)
            ->assertSee('value="perdida" selected', false);

        $this->get(route('credentials.index', ['type' => 'licenses']))
            ->assertOk()
            ->assertSee('value="Archivada manualmente"', false);
    }

    public function test_outlook_create_form_has_safe_defaults_and_password_toggle(): void
    {
        $this->actingAs($this->user('soporte'))
            ->get(route('outlook-accounts.create'))
            ->assertOk()
            ->assertSee('mail.totalground.com')
            ->assertSee('value="995"', false)
            ->assertSee('value="465"', false)
            ->assertSee('SSL/TLS')
            ->assertSee('data-password-toggle="outlook-password"', false);
    }

    public function test_duplicate_outlook_email_has_a_clear_validation_message(): void
    {
        $support = $this->user('soporte');
        OutlookAccount::create(['correo' => 'duplicado@example.test', 'contraseña' => 'secret', 'estatus' => 'ACTIVA']);

        $this->actingAs($support)
            ->post(route('outlook-accounts.store'), [
                'correo' => 'duplicado@example.test',
                'contraseña' => 'another-secret',
                'estatus' => 'ACTIVA',
            ])
            ->assertSessionHasErrors(['correo' => 'Este correo ya esta registrado en Outlook. Usa otro correo o edita el registro existente.']);
    }

    public function test_full_search_does_not_truncate_many_matches_from_one_table(): void
    {
        $this->actingAs($this->user('consulta'));

        foreach (range(1, 12) as $number) {
            SoftwareLicense::create([
                'name' => 'cPanel sitio '.$number,
                'type' => 'Credencial',
                'status' => 'Activa',
            ]);
        }

        $this->get(route('search.index', ['q' => 'cpanel']))
            ->assertOk()
            ->assertSeeText('12 resultados')
            ->assertSee('cPanel sitio 1')
            ->assertSee('cPanel sitio 12');
    }

    public function test_consultation_cannot_see_or_search_credential_secrets(): void
    {
        $account = AccountCredential::create([
            'email' => 'visible@example.test', 'password' => 'Hidden-Account-Secret',
            'account_type' => 'Gmail', 'status' => 'Activo',
        ]);
        $outlook = OutlookAccount::create([
            'correo' => 'outlook@example.test', 'contraseña' => 'Hidden-Outlook-Secret', 'estatus' => 'ACTIVA',
        ]);
        $license = SoftwareLicense::create([
            'name' => 'Producto visible', 'key_value' => 'Hidden-License-Key', 'password' => 'Hidden-License-Password',
        ]);

        $this->actingAs($this->user('consulta'));
        $this->get(route('account-credentials.show', $account))->assertOk()->assertDontSee('Hidden-Account-Secret');
        $this->get(route('outlook-accounts.show', $outlook))->assertOk()->assertDontSee('Hidden-Outlook-Secret');
        $this->get(route('software-licenses.show', $license))->assertOk()
            ->assertDontSee('Hidden-License-Key')->assertDontSee('Hidden-License-Password');
        $this->getJson(route('search.suggestions', ['q' => 'Hidden-License-Key']))
            ->assertOk()->assertJsonCount(0, 'results');
        $this->get(route('credentials.index', ['type' => 'accounts', 'search' => 'Hidden-Account-Secret']))
            ->assertOk()->assertDontSee('visible@example.test');
    }

    public function test_blank_updates_preserve_all_existing_secrets(): void
    {
        $support = $this->user('soporte');
        $account = AccountCredential::create([
            'email' => 'account@example.test', 'password' => 'Original-Account',
            'account_type' => 'Gmail', 'status' => 'Activo',
        ]);
        $outlook = OutlookAccount::create([
            'correo' => 'mail@example.test', 'contraseña' => 'Original-Outlook', 'estatus' => 'ACTIVA',
        ]);
        $license = SoftwareLicense::create([
            'name' => 'Suite', 'key_value' => 'Original-Key', 'password' => 'Original-License', 'status' => 'Activa',
        ]);

        $this->actingAs($support)->put(route('account-credentials.update', $account), [
            'email' => $account->email, 'password' => '', 'account_type' => 'Gmail', 'status' => 'Inactivo',
        ])->assertSessionHasNoErrors();
        $this->put(route('outlook-accounts.update', $outlook), [
            'correo' => $outlook->correo, 'contraseña' => '', 'estatus' => 'BAJA',
        ])->assertSessionHasNoErrors();
        $this->put(route('software-licenses.update', $license), [
            'name' => 'Suite renovada', 'key_value' => '', 'password' => '', 'status' => 'Activa',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Original-Account', $account->fresh()->password);
        $this->assertSame('Original-Outlook', $outlook->fresh()->{'contraseña'});
        $this->assertSame('Original-Key', $license->fresh()->key_value);
        $this->assertSame('Original-License', $license->fresh()->password);
    }

    public function test_license_audit_and_realtime_event_never_expose_secrets(): void
    {
        $this->actingAs($this->user('soporte'))->post(route('software-licenses.store'), [
            'name' => 'Suite protegida', 'type' => 'Licencia',
            'key_value' => 'License-Key-Secret', 'password' => 'License-Password-Secret', 'status' => 'Activa',
        ])->assertSessionHasNoErrors();

        $audit = AuditLog::where('entity', 'licenses')->firstOrFail();
        $this->assertSame('License-Key-Secret', $audit->after_data['key_value']);
        $this->assertSame('License-Password-Secret', $audit->after_data['password']);
        $this->getJson(route('inventory-events.poll', ['since' => $audit->id - 1]))
            ->assertOk()->assertJsonPath('events.0.entity', 'licenses')
            ->assertDontSee('License-Key-Secret')->assertDontSee('License-Password-Secret');
    }

    public function test_license_update_records_and_displays_previous_password(): void
    {
        $admin = $this->user('admin');
        $license = SoftwareLicense::create([
            'name' => 'cPanel Servidor',
            'type' => 'Credencial',
            'key_value' => 'cpanel_user',
            'password' => 'OldPassword123!',
            'status' => 'Activa',
        ]);

        $this->actingAs($admin)->put(route('software-licenses.update', $license), [
            'name' => 'cPanel Servidor',
            'type' => 'Credencial',
            'key_value' => 'cpanel_user',
            'password' => 'NewPassword456!',
            'status' => 'Activa',
        ])->assertSessionHasNoErrors()->assertRedirect(route('software-licenses.show', $license));

        $audit = AuditLog::where('entity', 'licenses')->where('entity_id', (string) $license->id)->latest('id')->firstOrFail();
        $this->assertSame('OldPassword123!', $audit->before_data['password']);
        $this->assertSame('NewPassword456!', $audit->after_data['password']);

        $response = $this->actingAs($admin)->get(route('software-licenses.show', $license));
        $response->assertOk()
            ->assertSee('Bitacora del registro')
            ->assertSee('OldPassword123!')
            ->assertSee('NewPassword456!')
            ->assertSee('••••••••')
            ->assertSee('audit-eye-btn')
            ->assertSee('Copiar anterior');
    }

    public function test_consultation_cannot_modify_credentials(): void
    {
        $account = AccountCredential::create(['email' => 'locked@example.test', 'account_type' => 'Personal', 'status' => 'Activo']);
        $outlook = OutlookAccount::create(['correo' => 'locked-outlook@example.test', 'contraseña' => 'secret']);
        $license = SoftwareLicense::create(['name' => 'Licencia bloqueada']);
        $this->actingAs($this->user('consulta'));

        $this->get(route('account-credentials.create'))->assertForbidden();
        $this->get(route('outlook-accounts.edit', $outlook))->assertForbidden();
        $this->delete(route('software-licenses.destroy', $license))->assertForbidden();
        $this->delete(route('account-credentials.destroy', $account))->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => 'credentials_'.$role.'_'.User::count(),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }
}
