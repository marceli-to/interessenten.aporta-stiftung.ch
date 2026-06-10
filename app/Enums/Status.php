<?php

namespace App\Enums;

enum Status: string implements LabeledEnum
{
	case Opened = 'opened';
	case Extended = 'extended';
	case Archived = 'archived';
	case Knif = 'knif';

	public function label(): string
	{
		return match ($this) {
			self::Opened => 'Eröffnet',
			self::Extended => 'Verlängert',
			self::Archived => 'Archiviert',
			self::Knif => 'KNIF',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Opened => 1,
			self::Extended => 2,
			self::Archived => 3,
			self::Knif => 4,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
