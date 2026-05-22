<?php

namespace App\Enums;

enum EmploymentStatus: string implements LabeledEnum
{
	case Employed = 'employed';
	case Student = 'student';
	case SelfEmployed = 'self_employed';
	case Unemployed = 'unemployed';
	case Retired = 'retired';
	case Homemaker = 'homemaker';

	public function label(): string
	{
		return match ($this) {
			self::Employed => 'Angestellt',
			self::Student => 'Student*in',
			self::SelfEmployed => 'Selbständig',
			self::Unemployed => 'Arbeitslos',
			self::Retired => 'Im Ruhestand (pensioniert)',
			self::Homemaker => 'Familienmanager*in',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Employed => 1,
			self::Student => 2,
			self::SelfEmployed => 3,
			self::Unemployed => 4,
			self::Retired => 5,
			self::Homemaker => 6,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
