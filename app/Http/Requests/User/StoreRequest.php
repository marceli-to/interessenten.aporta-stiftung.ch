<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'firstname' => ['required', 'string', 'max:255'],
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
			'password' => ['required', 'string', 'min:6'],
		];
	}

	public function messages(): array
	{
		return [
			'firstname.required' => 'Vorname ist erforderlich',
			'firstname.max' => 'Vorname darf maximal 255 Zeichen lang sein',
			'name.required' => 'Name ist erforderlich',
			'name.max' => 'Name darf maximal 255 Zeichen lang sein',
			'email.required' => 'E-Mail ist erforderlich',
			'email.email' => 'Bitte eine gültige E-Mail-Adresse eingeben',
			'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet',
			'password.required' => 'Passwort ist erforderlich',
			'password.min' => 'Passwort muss mindestens 6 Zeichen lang sein',
		];
	}
}
