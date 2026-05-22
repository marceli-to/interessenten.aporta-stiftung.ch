<?php

namespace App\Enums;

enum RelationshipToMain: string implements LabeledEnum
{
	case Spouse = 'spouse';
	case RegisteredPartner = 'registered_partner';
	case LifePartner = 'life_partner';
	case Roommate = 'roommate';

	public function label(): string
	{
		return match ($this) {
			self::Spouse => 'Ehepartner*in',
			self::RegisteredPartner => 'Lebenspartner*in mit eingetragener Partnerschaft',
			self::LifePartner => 'Lebenspartner*in',
			self::Roommate => 'Mitbewohner*in',
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::Spouse => 1,
			self::RegisteredPartner => 2,
			self::LifePartner => 3,
			self::Roommate => 4,
		};
	}

	public function active(): bool
	{
		return true;
	}
}
