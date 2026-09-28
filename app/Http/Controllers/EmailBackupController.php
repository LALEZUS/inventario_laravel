<?php
namespace App\Http\Controllers;
use App\Http\Requests\EmailBackupRequest; use App\Models\AuditLog; use App\Models\EmailBackup; use App\Services\AuditLogger; use Illuminate\Http\RedirectResponse; use Illuminate\Support\Facades\DB; use Illuminate\View\View;
class EmailBackupController extends Controller
{
    public function create(): View {$this->authorize('create',EmailBackup::class);return view('emails.backups.form',['emailBackup'=>new EmailBackup]);}
    public function store(EmailBackupRequest $r,AuditLogger $a): RedirectResponse {$m=DB::transaction(function()use($r,$a){$m=EmailBackup::create($r->validated());$a->record('create','email_backups',$m->id,null,$m->getAttributes());return $m;});return redirect()->route('email-backups.show',$m)->with('success','Reenvio registrado.');}
    public function show(EmailBackup $emailBackup): View {$this->authorize('view',$emailBackup);$auditLogs=request()->user()->can('viewAudit',$emailBackup)?AuditLog::where('entity','email_backups')->where('entity_id',(string)$emailBackup->id)->latest()->limit(20)->get():collect();return view('emails.backups.show',compact('emailBackup','auditLogs'));}
    public function edit(EmailBackup $emailBackup): View {$this->authorize('update',$emailBackup);return view('emails.backups.form',compact('emailBackup'));}
    public function update(EmailBackupRequest $r,EmailBackup $emailBackup,AuditLogger $a): RedirectResponse {$before=$emailBackup->getAttributes();DB::transaction(function()use($r,$emailBackup,$before,$a){$emailBackup->update($r->validated());$a->record('update','email_backups',$emailBackup->id,$before,$emailBackup->fresh()->getAttributes());});return redirect()->route('email-backups.show',$emailBackup)->with('success','Reenvio actualizado.');}
    public function destroy(EmailBackup $emailBackup,AuditLogger $a): RedirectResponse {$this->authorize('delete',$emailBackup);$before=$emailBackup->getAttributes();DB::transaction(function()use($emailBackup,$before,$a){$emailBackup->delete();$a->record('delete','email_backups',$emailBackup->id,$before,null);});return redirect()->route('emails.index',['type'=>'backups'])->with('success','Reenvio eliminado.');}
}
