<?php
namespace App\Http\Requests;
use App\Models\EmailBackup; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Support\Facades\Gate;
class EmailBackupRequest extends FormRequest
{
    public function authorize(): bool { $m=$this->route('emailBackup'); return $m instanceof EmailBackup ? Gate::allows('update',$m) : Gate::allows('create',EmailBackup::class); }
    protected function prepareForValidation(): void { $this->merge(['is_done'=>$this->boolean('is_done'),'is_archived'=>$this->boolean('is_archived')]); }
    public function rules(): array { return ['original_name'=>['required','string','max:255'],'original_email'=>['required','email:rfc','max:255'],'backup_name'=>['required','string','max:255'],'backup_email'=>['required','email:rfc','max:255'],'start_date'=>['nullable','date'],'end_date'=>['nullable','date','after_or_equal:start_date'],'is_done'=>['boolean'],'is_archived'=>['boolean'],'comments'=>['nullable','string']]; }
}
