<?php
namespace App\Http\Requests;
use App\Models\MicrosoftEmail; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Support\Facades\Gate; use Illuminate\Validation\Rule;
class MicrosoftEmailRequest extends FormRequest
{
    public function authorize(): bool { $m=$this->route('microsoftEmail'); return $m instanceof MicrosoftEmail ? Gate::allows('update',$m) : Gate::allows('create',MicrosoftEmail::class); }
    public function rules(): array { $model=$this->route('microsoftEmail');$id=$model?->id;$statuses=array_values(array_unique(array_filter([...config('inventory.catalogs.email_statuses'),$model?->status])));return ['email'=>['required','email:rfc','max:255',Rule::unique('microsoft_emails')->ignore($id)],'password'=>[$id?'nullable':'required','string','max:255'],'status'=>['required',Rule::in($statuses)],'activation_date'=>['nullable','date'],'renewal_date'=>['nullable','date','after_or_equal:activation_date'],'admin_url'=>['nullable','url','max:255'],'admin_account'=>['nullable','string','max:255'],'comments'=>['nullable','string']]; }
}
