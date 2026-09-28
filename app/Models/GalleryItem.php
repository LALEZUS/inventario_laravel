<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalleryItem extends Model { protected $table='gallery'; public const CREATED_AT='upload_date'; public const UPDATED_AT=null; protected $guarded=[]; protected function casts(): array{return ['upload_date'=>'datetime'];} }
