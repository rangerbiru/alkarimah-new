<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserProfileRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    { {
            $user = Auth::user();

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'phone' => 'required|string|max:15',
            ];

            if ($this->method() == 'PUT') {
                $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $user->id;
                $rules['phone'] = 'required|string|max:15|unique:users,phone,' . $user->id;
            }

            return $rules;
        }
    }
}
