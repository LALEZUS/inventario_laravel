<?php
namespace App\Policies; use App\Models\Tutorial; use App\Models\User;
class TutorialPolicy { public function viewAny(User $u):bool{return true;} public function view(User $u,Tutorial $m):bool{return true;} public function create(User $u):bool{return in_array($u->role,['admin','soporte'],true);} public function update(User $u,Tutorial $m):bool{return $this->create($u);} public function delete(User $u,Tutorial $m):bool{return $u->role==='admin';} public function viewAudit(User $u,Tutorial $m):bool{return $u->role==='admin';} }
