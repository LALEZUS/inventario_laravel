<?php
namespace App\Http\Controllers;
use App\Http\Requests\OfficeEmailRequest; use App\Models\AuditLog; use App\Models\OfficeEmail; use App\Services\AuditLogger; use Illuminate\Http\RedirectResponse; use Illuminate\Support\Facades\DB; use Illuminate\View\View;
class OfficeEmailController extends Controller
{
    public function create(): View {$this->authorize('create',OfficeEmail::class);return view('emails.account-form',['account'=>new OfficeEmail,'kind'=>'Correo Windows','routePrefix'=>'office-emails','hasAdmin'=>false]);}
    public function store(OfficeEmailRequest $r,AuditLogger $a): RedirectResponse {$m=DB::transaction(function()use($r,$a){$m=OfficeEmail::create($r->validated());$a->record('create','office_emails',$m->id,null,$m->getAttributes());return $m;});return redirect()->route('office-emails.show',$m)->with('success','Correo Windows registrado.');}
    public function show(OfficeEmail $officeEmail): View {$this->authorize('view',$officeEmail);$auditLogs=$this->logs($officeEmail);return view('emails.account-show',['account'=>$officeEmail,'auditLogs'=>$auditLogs,'kind'=>'Correo Windows','routePrefix'=>'office-emails','type'=>'windows','hasAdmin'=>false]);}
    public function edit(OfficeEmail $officeEmail): View {$this->authorize('update',$officeEmail);return view('emails.account-form',['account'=>$officeEmail,'kind'=>'Correo Windows','routePrefix'=>'office-emails','hasAdmin'=>false]);}
    public function update(OfficeEmailRequest $r,OfficeEmail $officeEmail,AuditLogger $a): RedirectResponse {$before=$officeEmail->getAttributes();$data=$r->validated();if(blank($data['password']??null))unset($data['password']);DB::transaction(function()use($officeEmail,$data,$before,$a){$officeEmail->update($data);$a->record('update','office_emails',$officeEmail->id,$before,$officeEmail->fresh()->getAttributes());});return redirect()->route('office-emails.show',$officeEmail)->with('success','Correo actualizado.');}
    public function destroy(OfficeEmail $officeEmail,AuditLogger $a): RedirectResponse {$this->authorize('delete',$officeEmail);$before=$officeEmail->getAttributes();DB::transaction(function()use($officeEmail,$before,$a){$officeEmail->delete();$a->record('delete','office_emails',$officeEmail->id,$before,null);});return redirect()->route('emails.index',['type'=>'windows'])->with('success','Correo eliminado.');}
    private function logs(OfficeEmail $m){return request()->user()->can('viewAudit',$m)?AuditLog::where('entity','office_emails')->where('entity_id',(string)$m->id)->latest()->limit(20)->get():collect();}
}
