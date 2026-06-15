<?php

namespace App\Enums;

enum Floor: string implements LabeledEnum
{
	case Ground = 'eg_hochparterre';
	case Upper = 'obergeschoss';

	public function label(): string
	{
		return match ($this) {
			self::Ground => 'EG/Hochparterre',
			self::Upper => 'Obergeschoss',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Ground => 0,
			self::Upper => 1,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
