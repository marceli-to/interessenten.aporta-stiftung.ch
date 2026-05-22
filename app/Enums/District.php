<?php

namespace App\Enums;

enum District: string implements LabeledEnum
{
	case Kreis4 = 'kreis_4';
	case Kreis5 = 'kreis_5';
	case Kreis6 = 'kreis_6';
	case Kreis7 = 'kreis_7';
	case Kreis8 = 'kreis_8';
	case Kreis10 = 'kreis_10';

	public function label(): string
	{
		return match ($this) {
			self::Kreis4 => 'Kreis 4',
			self::Kreis5 => 'Kreis 5',
			self::Kreis6 => 'Kreis 6',
			self::Kreis7 => 'Kreis 7',
			self::Kreis8 => 'Kreis 8',
			self::Kreis10 => 'Kreis 10',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Kreis4 => 4,
			self::Kreis5 => 5,
			self::Kreis6 => 6,
			self::Kreis7 => 7,
			self::Kreis8 => 8,
			self::Kreis10 => 10,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
