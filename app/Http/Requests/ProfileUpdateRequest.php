<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        $rules = [
            'username' => [
                'required', 
                'string', 
                'max:50', 
                Rule::unique(User::class)->ignore($user->id)
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ];

        // Si el usuario logueado es un médico, agregamos las reglas extra
        if ($user->medico) {
            $rules['nombre'] = ['required', 'string', 'max:255'];
            $rules['apellido'] = ['required', 'string', 'max:255'];
            $rules['telefono'] = ['nullable', 'string', 'max:30'];
            $rules['firmaFile'] = ['nullable', 'image', 'max:2048'];
        }

        return $rules;
    }
}