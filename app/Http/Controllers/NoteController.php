<?php
namespace App\Http\Controllers;
use App\Models\AuditLog; use App\Models\Note; use App\Services\AuditLogger; use App\Support\RecentRecords; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\View\View;
class NoteController extends Controller
{
 public function index(Request $r):View{$this->authorize('viewAny',Note::class);$s=trim((string)$r->query('search'));$query=Note::when($s!=='',fn($q)=>$q->where(fn($q)=>$q->where('title','like','%'.$s.'%')->orWhere('content','like','%'.$s.'%')));$items=RecentRecords::apply($query,$r,fn($q)=>$q->latest())->paginate(20)->withQueryString();return view('notes.index',compact('items','s'));}
 public function create():View{$this->authorize('create',Note::class);return view('notes.form',['note'=>new Note]);}
 public function store(Request $r,AuditLogger $a):RedirectResponse{$this->authorize('create',Note::class);$m=Note::create($this->data($r));$a->record('create','notes',$m->id,null,$m->getAttributes());return redirect()->route('notes.show',$m)->with('success','Nota creada.');}
 public function show(Note $note):View{$this->authorize('view',$note);$auditLogs=request()->user()->can('viewAudit',$note)?AuditLog::where('entity','notes')->where('entity_id',(string)$note->id)->latest()->limit(20)->get():collect();return view('notes.show',compact('note','auditLogs'));}
 public function edit(Note $note):View{$this->authorize('update',$note);return view('notes.form',compact('note'));}
 public function update(Request $r,Note $note,AuditLogger $a):RedirectResponse{$this->authorize('update',$note);$before=$note->getAttributes();$note->update($this->data($r));$a->record('update','notes',$note->id,$before,$note->fresh()->getAttributes());return redirect()->route('notes.show',$note)->with('success','Nota actualizada.');}
 public function destroy(Note $note,AuditLogger $a):RedirectResponse{$this->authorize('delete',$note);$before=$note->getAttributes();$note->delete();$a->record('delete','notes',$note->id,$before,null);return redirect()->route('notes.index')->with('success','Nota eliminada.');}
 private function data(Request $r):array{return $r->validate(['title'=>['required','string','max:255'],'content'=>['nullable','string']]);}
}
