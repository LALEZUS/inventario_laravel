<?php
namespace App\Http\Controllers;
use App\Models\AuditLog; use App\Models\FileCatalog; use App\Services\AuditLogger; use App\Services\LibraryFile; use App\Support\RecentRecords; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\Storage; use Illuminate\Support\Str; use Illuminate\View\View; use Symfony\Component\HttpFoundation\BinaryFileResponse;
class FileCatalogController extends Controller
{
 public function index(Request $r):View{
     $this->authorize('viewAny',FileCatalog::class);
     $s=trim((string)$r->query('search'));
     $type=trim((string)$r->query('type'));
     $query=FileCatalog::with('uploader')
         ->when($s!=='',fn($q)=>$q->where(fn($q)=>$q->where('original_name','like','%'.$s.'%')->orWhere('alias_name','like','%'.$s.'%')->orWhere('comments','like','%'.$s.'%')))
         ->when($type!==''&&$type!=='all',function($q) use ($type){
             match($type){
                 'archive'=>$q->where(fn($sq)=>$sq->where('original_name','like','%.zip')->orWhere('original_name','like','%.rar')->orWhere('original_name','like','%.7z')->orWhere('original_name','like','%.tar')->orWhere('original_name','like','%.gz')),
                 'installer'=>$q->where(fn($sq)=>$sq->where('original_name','like','%.exe')->orWhere('original_name','like','%.msi')->orWhere('original_name','like','%.bat')->orWhere('original_name','like','%.cmd')->orWhere('original_name','like','%.iso')),
                 'pdf'=>$q->where('original_name','like','%.pdf'),
                 'document'=>$q->where(fn($sq)=>$sq->where('original_name','like','%.doc%')->orWhere('original_name','like','%.xls%')->orWhere('original_name','like','%.ppt%')->orWhere('original_name','like','%.csv')),
                 'image'=>$q->where(fn($sq)=>$sq->where('original_name','like','%.png')->orWhere('original_name','like','%.jpg%')->orWhere('original_name','like','%.webp')->orWhere('original_name','like','%.svg')),
                 default=>$q
             };
         });
     $items=RecentRecords::apply($query,$r,fn($q)=>$q->latest('upload_date'),'upload_date')->paginate(25)->withQueryString();
     return view('files.index',compact('items','s','type'));
 }
 public function create():View{$this->authorize('create',FileCatalog::class);return view('files.form',['fileCatalog'=>new FileCatalog]);}
 public function store(Request $r,AuditLogger $a):RedirectResponse{$this->authorize('create',FileCatalog::class);$d=$this->data($r,true);$f=$r->file('file');$path=$f->storeAs('library/files',now()->format('YmdHis').'-'.Str::uuid().'-'.Str::slug(pathinfo($f->getClientOriginalName(),PATHINFO_FILENAME)).'.'.strtolower($f->getClientOriginalExtension()),'local');$m=FileCatalog::create(['original_name'=>$f->getClientOriginalName(),'alias_name'=>$d['alias_name']??null,'file_path'=>'laravel-local:'.$path,'file_size'=>$f->getSize(),'uploaded_by'=>$r->user()->id,'comments'=>$d['comments']??null]);$a->record('create','ftp_catalog',$m->id,null,$m->getAttributes());return redirect()->route('files.show',$m)->with('success','Archivo agregado.');}
 public function show(FileCatalog $fileCatalog):View{$this->authorize('view',$fileCatalog);$fileCatalog->load('uploader');$auditLogs=request()->user()->can('viewAudit',$fileCatalog)?AuditLog::where('entity','ftp_catalog')->where('entity_id',(string)$fileCatalog->id)->latest()->limit(20)->get():collect();return view('files.show',compact('fileCatalog','auditLogs'));}
 public function edit(FileCatalog $fileCatalog):View{$this->authorize('update',$fileCatalog);return view('files.form',compact('fileCatalog'));}
 public function update(Request $r,FileCatalog $fileCatalog,AuditLogger $a):RedirectResponse{$this->authorize('update',$fileCatalog);$before=$fileCatalog->getAttributes();$d=$this->data($r,false);unset($d['file']);$fileCatalog->update($d);$a->record('update','ftp_catalog',$fileCatalog->id,$before,$fileCatalog->fresh()->getAttributes());return redirect()->route('files.show',$fileCatalog)->with('success','Archivo actualizado.');}
 public function download(FileCatalog $fileCatalog,LibraryFile $files):BinaryFileResponse{$this->authorize('view',$fileCatalog);return response()->download($files->path($fileCatalog->file_path,'ftp_files'),$fileCatalog->original_name);}
 public function preview(FileCatalog $fileCatalog,LibraryFile $files):BinaryFileResponse{
     $this->authorize('view',$fileCatalog);
     $path=$files->path($fileCatalog->file_path,'ftp_files');
     $mime=mime_content_type($path) ?: 'application/octet-stream';
     return response()->file($path,['Content-Type'=>$mime,'Cache-Control'=>'no-cache, private']);
 }
 public function destroy(FileCatalog $fileCatalog,AuditLogger $a,LibraryFile $files):RedirectResponse{$this->authorize('delete',$fileCatalog);$before=$fileCatalog->getAttributes();$files->delete($fileCatalog->file_path,'ftp_files');$fileCatalog->delete();$a->record('delete','ftp_catalog',$fileCatalog->id,$before,null);return redirect()->route('files.index')->with('success','Archivo eliminado.');}
 private function data(Request $r,bool $required):array{return $r->validate(['alias_name'=>['nullable','string','max:255'],'comments'=>['nullable','string'],'file'=>[$required?'required':'nullable','file']]);}
}
