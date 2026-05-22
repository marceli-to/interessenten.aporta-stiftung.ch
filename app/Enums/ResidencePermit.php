<?php

namespace App\Enums;

enum ResidencePermit: string implements LabeledEnum
{
	case B = 'B';
	case C = 'C';
	case Ci = 'Ci';
	case G = 'G';
	case L = 'L';
	case F = 'F';
	case N = 'N';
	case S = 'S';

	public function label(): string
	{
		return $this->value;
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::B => 1,
			self::C => 2,
			self::Ci => 3,
			self::G => 4,
			self::L => 5,
			self::F => 6,
			self::N => 7,
			self::S => 8,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
