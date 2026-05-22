<?php

namespace App\Enums;

enum Salutation: string implements LabeledEnum
{
	case Frau = 'frau';
	case Herr = 'herr';
	case Other = 'other';

	public function label(): string
	{
		return match ($this) {
			self::Frau => 'Frau',
			self::Herr => 'Herr',
			self::Other => 'Andere',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Frau => 1,
			self::Herr => 2,
			self::Other => 3,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
