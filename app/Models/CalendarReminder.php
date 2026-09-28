<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CalendarReminder extends Model { protected $guarded=[]; protected function casts():array{return ['event_date'=>'date','is_done'=>'boolean'];} }
