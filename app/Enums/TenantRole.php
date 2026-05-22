<?php

namespace App\Enums;

enum TenantRole: string implements LabeledEnum
{
	case MainTenant = 'main_tenant';
	case Subtenant = 'subtenant';

	public function label(): string
	{
		return match ($this) {
			self::MainTenant => 'Hauptmieter*in',
			self::Subtenant => 'Untermieter*in',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::MainTenant => 1,
			self::Subtenant => 2,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
