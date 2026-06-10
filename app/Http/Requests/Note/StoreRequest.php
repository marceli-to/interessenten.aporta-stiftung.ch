<?php

namespace App\Http\Requests\Note;

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
			'body' => ['required', 'string', 'max:10000'],
		];
	}

	public function messages(): array
	{
		return [
			'body.required' => 'Bitte Text eingeben',
			'body.max' => 'Notiz darf maximal 10000 Zeichen lang sein',
		];
	}
}
