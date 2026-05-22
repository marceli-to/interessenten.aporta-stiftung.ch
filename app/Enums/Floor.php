<?php

namespace App\Enums;

enum Floor: string implements LabeledEnum
{
	case Hochparterre = 'hochparterre';
	case Floor1 = 'floor_1';
	case Floor2 = 'floor_2';
	case Floor3 = 'floor_3';
	case Floor4 = 'floor_4';
	case Floor5 = 'floor_5';
	case Floor6 = 'floor_6';

	public function label(): string
	{
		return match ($this) {
			self::Hochparterre => 'Hochparterre',
			self::Floor1 => '1. Stock',
			self::Floor2 => '2. Stock',
			self::Floor3 => '3. Stock',
			self::Floor4 => '4. Stock',
			self::Floor5 => '5. Stock',
			self::Floor6 => '6. Stock',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Hochparterre => 0,
			self::Floor1 => 1,
			self::Floor2 => 2,
			self::Floor3 => 3,
			self::Floor4 => 4,
			self::Floor5 => 5,
			self::Floor6 => 6,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
