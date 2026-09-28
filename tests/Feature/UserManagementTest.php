<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_open_user_management(): void
    {
        $this->actingAs($this->user('admin'))->get(route('users.index'))
            ->assertOk()->assertSee('Gestion de usuarios')->assertSee('Nuevo usuario');

        $this->actingAs($this->user('soporte'))->get(route('users.index'))->assertForbidden();
        $this->actingAs($this->user('consulta'))->get(route('users.create'))->assertForbidden();
    }

    public function test_administrator_can_create_a_user_with_a_hashed_password_and_audit_log(): void
    {
        $admin = $this->user('admin');
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'full_name' => 'Tecnico Inventario',
            'username' => 'tecnico.inventario',
            'role' => 'soporte',
            'password' => 'Password-2026',
            'password_confirmation' => 'Password-2026',
            'comments' => 'Acceso para mesa de ayuda.',
        ]);

        $created = User::where('username', 'tecnico.inventario')->firstOrFail();
        $response->assertRedirect(route('users.show', $created));
        $this->assertTrue(Hash::check('Password-2026', $created->password));
        $this->assertSame('Acceso para mesa de ayuda.', $created->comments);
        $this->assertDatabaseHas('audit_logs', ['entity' => 'users', 'entity_id' => (string) $created->id, 'action' => 'create']);
        $this->assertSame('[PROTECTED]', AuditLog::where('entity_id', (string) $created->id)->firstOrFail()->after_data['password']);
    }

    public function test_blank_password_on_update_preserves_the_existing_password(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('consulta');
        $password = $target->password;

        $this->actingAs($admin)->put(route('users.update', $target), [
            'full_name' => 'Consulta Actualizada',
            'username' => $target->username,
            'role' => 'consulta',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.show', $target));

        $this->assertSame($password, $target->fresh()->password);
        $this->assertSame('Consulta Actualizada', $target->fresh()->full_name);
    }

    public function test_administrator_cannot_remove_own_role_or_delete_own_account(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'full_name' => $admin->full_name,
            'username' => $admin->username,
            'role' => 'consulta',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);

        $this->delete(route('users.destroy', $admin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_administrator_can_delete_another_user(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('consulta');

        $this->actingAs($admin)->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('audit_logs', ['entity' => 'users', 'entity_id' => (string) $target->id, 'action' => 'delete']);
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => $role.'_'.str()->random(8),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.ucfirst($role),
            'role' => $role,
        ]);
    }
}
