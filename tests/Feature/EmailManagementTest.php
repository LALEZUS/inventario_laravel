<?php
namespace Tests\Feature;
use App\Models\AuditLog; use App\Models\EmailBackup; use App\Models\MicrosoftEmail; use App\Models\OfficeEmail; use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Support\Facades\Hash; use Tests\TestCase;
class EmailManagementTest extends TestCase
{
    use RefreshDatabase;
    public function test_support_can_create_and_immediately_find_microsoft_email(): void {
        $r=$this->actingAs($this->user('soporte'))->post(route('microsoft-emails.store'),['email'=>'zenith365@example.test','password'=>'Mail-Secret-2026','status'=>'ACTIVA','activation_date'=>'2026-01-01','renewal_date'=>'2027-01-01','admin_account'=>'tenant-admin@example.test']);
        $mail=MicrosoftEmail::firstOrFail();$r->assertSessionHasNoErrors()->assertRedirect(route('microsoft-emails.show',$mail));
        $this->get(route('emails.index',['search'=>'zenith365']))->assertOk()->assertSee('zenith365@example.test');
        $this->getJson(route('search.suggestions',['q'=>'zenith365']))->assertOk()->assertJsonPath('results.0.url',route('microsoft-emails.show',$mail));
        $this->assertSame('[PROTECTED]',AuditLog::where('entity','microsoft_emails')->firstOrFail()->after_data['password']);
    }
    public function test_email_forms_and_filters_use_status_catalogs(): void {
        $mail=OfficeEmail::create(['email'=>'legacy-state@example.test','status'=>'ESTADO LEGADO']);
        $this->actingAs($this->user('soporte'));
        $this->get(route('office-emails.edit',$mail))->assertOk()
            ->assertSee('<select name="status" required>',false)
            ->assertSee('value="ESTADO LEGADO" selected',false);
        $this->get(route('emails.index',['type'=>'windows']))->assertOk()
            ->assertSee('<select name="status">',false)
            ->assertSee('value="ESTADO LEGADO"',false);
    }
    public function test_consultation_cannot_see_or_search_mail_passwords(): void {
        $m=MicrosoftEmail::create(['email'=>'visible365@example.test','password'=>'Hidden-M365-Secret','status'=>'ACTIVA']);$w=OfficeEmail::create(['email'=>'visiblewin@example.test','password'=>'Hidden-Windows-Secret','status'=>'ACTIVA']);
        $this->actingAs($this->user('consulta'));
        $this->get(route('microsoft-emails.show',$m))->assertOk()->assertDontSee('Hidden-M365-Secret');
        $this->get(route('office-emails.show',$w))->assertOk()->assertDontSee('Hidden-Windows-Secret');
        $this->getJson(route('search.suggestions',['q'=>'Hidden-M365-Secret']))->assertOk()->assertJsonCount(0,'results');
        $this->get(route('emails.index',['search'=>'Hidden-Windows-Secret']))->assertOk()->assertDontSee('visiblewin@example.test');
    }
    public function test_blank_updates_preserve_mail_passwords(): void {
        $m=MicrosoftEmail::create(['email'=>'m@example.test','password'=>'Original-M','status'=>'ACTIVA']);$w=OfficeEmail::create(['email'=>'w@example.test','password'=>'Original-W','status'=>'ACTIVA']);$this->actingAs($this->user('soporte'));
        $this->put(route('microsoft-emails.update',$m),['email'=>$m->email,'password'=>'','status'=>'SUSPENDIDA'])->assertSessionHasNoErrors();
        $this->put(route('office-emails.update',$w),['email'=>$w->email,'password'=>'','status'=>'PERDIDA'])->assertSessionHasNoErrors();
        $this->assertSame('Original-M',$m->fresh()->password);$this->assertSame('Original-W',$w->fresh()->password);
    }
    public function test_backup_workflow_is_searchable_and_emits_realtime_event(): void {
        $r=$this->actingAs($this->user('soporte'))->post(route('email-backups.store'),['original_name'=>'Persona Origen','original_email'=>'origin@example.test','backup_name'=>'Persona Destino','backup_email'=>'target@example.test','start_date'=>'2026-08-01','is_done'=>1]);
        $backup=EmailBackup::firstOrFail();$r->assertSessionHasNoErrors()->assertRedirect(route('email-backups.show',$backup));
        $this->getJson(route('search.suggestions',['q'=>'target@example.test']))->assertOk()->assertJsonPath('results.0.url',route('email-backups.show',$backup));
        $audit=AuditLog::where('entity','email_backups')->firstOrFail();$this->getJson(route('inventory-events.poll',['since'=>$audit->id-1]))->assertOk()->assertJsonPath('events.0.entity','email_backups');
    }
    public function test_consultation_cannot_modify_email_records(): void {
        $m=MicrosoftEmail::create(['email'=>'locked365@example.test','status'=>'ACTIVA']);$w=OfficeEmail::create(['email'=>'lockedwin@example.test','status'=>'ACTIVA']);$b=EmailBackup::create(['original_name'=>'A','original_email'=>'a@example.test','backup_name'=>'B','backup_email'=>'b@example.test']);$this->actingAs($this->user('consulta'));
        $this->get(route('microsoft-emails.create'))->assertForbidden();$this->get(route('office-emails.edit',$w))->assertForbidden();$this->delete(route('email-backups.destroy',$b))->assertForbidden();$this->delete(route('microsoft-emails.destroy',$m))->assertForbidden();
    }
    public function test_historical_zero_dates_render_as_empty_values(): void {
        $mail=OfficeEmail::create(['email'=>'historic@example.test','status'=>'ACTIVA','activation_date'=>'0000-00-00','renewal_date'=>'0000-00-00']);
        $backup=EmailBackup::create(['original_name'=>'Origen','original_email'=>'old@example.test','backup_name'=>'Destino','backup_email'=>'new@example.test','end_date'=>'0000-00-00']);
        $this->actingAs($this->user('consulta'));
        $this->get(route('office-emails.show',$mail))->assertOk()->assertSee('historic@example.test');
        $this->get(route('email-backups.show',$backup))->assertOk()->assertSee('old@example.test');
        $this->assertNull($mail->dateValue('activation_date'));$this->assertNull($backup->dateValue('end_date'));
    }
    private function user(string $role): User {return User::create(['username'=>'email_'.$role.'_'.User::count(),'password'=>Hash::make('password'),'full_name'=>'Usuario '.$role,'role'=>$role]);}
}
