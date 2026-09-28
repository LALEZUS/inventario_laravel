<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MicrosoftEmail extends Model
{
    protected $guarded = [];
    protected $hidden = ['password'];
    public function dateValue(string $field): ?string { $value=$this->getRawOriginal($field); return filled($value)&&$value!=='0000-00-00'?$value:null; }
    public function dateLabel(string $field): ?string { $value=$this->dateValue($field); return $value?date('d/m/Y',strtotime($value)):null; }
}
