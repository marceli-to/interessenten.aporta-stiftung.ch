<?php

namespace App\Enums;

enum RentDuration: string implements LabeledEnum
{
	case LessThan1Year = 'less_than_1_year';
	case OneToTwoYears = '1_to_2_years';
	case MoreThan2Years = 'more_than_2_years';

	public function label(): string
	{
		return match ($this) {
			self::LessThan1Year => 'Weniger als 1 Jahr',
			self::OneToTwoYears => '1 bis 2 Jahre',
			self::MoreThan2Years => 'Mehr als 2 Jahre',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::LessThan1Year => 1,
			self::OneToTwoYears => 2,
			self::MoreThan2Years => 3,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
