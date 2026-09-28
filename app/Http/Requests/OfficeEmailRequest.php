<?php
namespace App\Http\Requests;
use App\Models\OfficeEmail; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Support\Facades\Gate; use Illuminate\Validation\Rule;
class OfficeEmailRequest extends FormRequest
{
    public function authorize(): bool { $m=$this->route('officeEmail'); return $m instanceof OfficeEmail ? Gate::allows('update',$m) : Gate::allows('create',OfficeEmail::class); }
    public function rules(): array { $model=$this->route('officeEmail');$id=$model?->id;$statuses=array_values(array_unique(array_filter([...config('inventory.catalogs.email_statuses'),$model?->status])));return ['email'=>['required','email:rfc','max:255',Rule::unique('office_emails')->ignore($id)],'password'=>[$id?'nullable':'required','string','max:255'],'status'=>['required',Rule::in($statuses)],'activation_date'=>['nullable','date'],'renewal_date'=>['nullable','date','after_or_equal:activation_date'],'comments'=>['nullable','string']]; }
}
