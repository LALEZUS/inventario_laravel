<?php
namespace App\Http\Controllers;
use App\Models\BackupRun; use App\Services\AuditLogger; use App\Services\DatabaseBackup; use Illuminate\Http\RedirectResponse; use Illuminate\View\View; use Symfony\Component\HttpFoundation\BinaryFileResponse;
class BackupController extends Controller
{
 public function index():View{$this->authorize('viewAny',BackupRun::class);return view('backups.index',['items'=>BackupRun::latest()->paginate(20)]);}
 public function store(DatabaseBackup $service,AuditLogger $audit):RedirectResponse{$this->authorize('create',BackupRun::class);$run=$service->create();$audit->record('create','backup_runs',$run->id,null,$run->getAttributes());return back()->with('success','Respaldo generado.');}
 public function download(BackupRun $backupRun,DatabaseBackup $service):BinaryFileResponse{$this->authorize('view',$backupRun);return response()->download($service->path($backupRun),basename(str_replace('laravel-local:','',$backupRun->filename)));}
}
