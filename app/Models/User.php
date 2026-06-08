<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
	use HasFactory, Notifiable, SoftDeletes;

	protected $fillable = [
		'firstname',
		'name',
		'email',
		'password',
		'role',
		'active',
	];

	protected $hidden = [
		'password',
		'remember_token',
	];

	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
			'active' => 'boolean',
		];
	}

	protected function fullName(): Attribute
	{
		return Attribute::make(
			get: fn () => trim("{$this->firstname} {$this->name}"),
		);
	}

	protected function initials(): Attribute
	{
		return Attribute::make(
			get: fn () => Str::upper(Str::substr($this->firstname, 0, 1) . Str::substr($this->name, 0, 1)),
		);
	}
}
