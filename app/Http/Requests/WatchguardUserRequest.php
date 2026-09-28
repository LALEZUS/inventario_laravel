<?php

namespace App\Http\Requests;

use App\Models\WatchguardUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class WatchguardUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('watchguardUser');
        return $user instanceof WatchguardUser ? Gate::allows('update', $user) : Gate::allows('create', WatchguardUser::class);
    }

    public function rules(): array
    {
        $creating = ! $this->route('watchguardUser');
        return [
            'username' => ['required', 'string', 'max:100'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'string', 'max:150'],
            'area' => ['nullable', 'string', 'max:100'],
            'ip' => ['nullable', 'ip'],
            'comments' => ['nullable', 'string'],
        ];
    }
}
