<?php
namespace App\Policies; use App\Models\Note; use App\Models\User;
class NotePolicy { public function viewAny(User $u):bool{return true;} public function view(User $u,Note $m):bool{return true;} public function create(User $u):bool{return in_array($u->role,['admin','soporte'],true);} public function update(User $u,Note $m):bool{return $this->create($u);} public function delete(User $u,Note $m):bool{return $u->role==='admin';} public function viewAudit(User $u,Note $m):bool{return $u->role==='admin';} }
