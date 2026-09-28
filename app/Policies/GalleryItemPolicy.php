<?php
namespace App\Policies; use App\Models\GalleryItem; use App\Models\User;
class GalleryItemPolicy { public function viewAny(User $u):bool{return true;} public function view(User $u,GalleryItem $m):bool{return true;} public function create(User $u):bool{return in_array($u->role,['admin','soporte'],true);} public function update(User $u,GalleryItem $m):bool{return $this->create($u);} public function delete(User $u,GalleryItem $m):bool{return $u->role==='admin';} public function viewAudit(User $u,GalleryItem $m):bool{return $u->role==='admin';} }
