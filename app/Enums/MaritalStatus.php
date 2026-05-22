<?php

namespace App\Enums;

enum MaritalStatus: string implements LabeledEnum
{
	case Single = 'single';
	case Married = 'married';
	case Divorced = 'divorced';
	case Widowed = 'widowed';
	case DissolvedPartnership = 'dissolved_partnership';
	case RegisteredPartnership = 'registered_partnership';

	public function label(): string
	{
		return match ($this) {
			self::Single => 'ledig',
			self::Married => 'verheiratet',
			self::Divorced => 'geschieden',
			self::Widowed => 'verwitwet',
			self::DissolvedPartnership => 'aufgelöste Partnerschaft',
			self::RegisteredPartnership => 'eingetragene Partnerschaft',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Single => 1,
			self::Married => 2,
			self::Divorced => 3,
			self::Widowed => 4,
			self::DissolvedPartnership => 5,
			self::RegisteredPartnership => 6,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
