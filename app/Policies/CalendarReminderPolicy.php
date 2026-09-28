<?php
namespace App\Policies; use App\Models\CalendarReminder; use App\Models\User;
class CalendarReminderPolicy { public function viewAny(User $u):bool{return true;} public function view(User $u,CalendarReminder $m):bool{return true;} public function create(User $u):bool{return in_array($u->role,['admin','soporte'],true);} public function update(User $u,CalendarReminder $m):bool{return $this->create($u);} public function delete(User $u,CalendarReminder $m):bool{return $u->role==='admin';} }
