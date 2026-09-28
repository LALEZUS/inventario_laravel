<?php
namespace App\Policies; use App\Models\FileCatalog; use App\Models\User;
class FileCatalogPolicy { public function viewAny(User $u):bool{return true;} public function view(User $u,FileCatalog $m):bool{return true;} public function create(User $u):bool{return in_array($u->role,['admin','soporte'],true);} public function update(User $u,FileCatalog $m):bool{return $this->create($u);} public function delete(User $u,FileCatalog $m):bool{return $u->role==='admin';} public function viewAudit(User $u,FileCatalog $m):bool{return $u->role==='admin';} }
