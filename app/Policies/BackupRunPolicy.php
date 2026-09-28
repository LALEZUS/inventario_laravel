<?php
namespace App\Policies; use App\Models\BackupRun; use App\Models\User;
class BackupRunPolicy { public function viewAny(User $u):bool{return $u->role==='admin';} public function view(User $u,BackupRun $m):bool{return $u->role==='admin';} public function create(User $u):bool{return $u->role==='admin';} public function delete(User $u,BackupRun $m):bool{return $u->role==='admin';} }
