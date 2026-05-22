<?php

namespace App\Enums;

enum Room: string implements LabeledEnum
{
	case Rooms2_0 = 'rooms_2_0';
	case Rooms2_5 = 'rooms_2_5';
	case Rooms3_0 = 'rooms_3_0';
	case Rooms3_5 = 'rooms_3_5';
	case Rooms4_0 = 'rooms_4_0';
	case Rooms4_5 = 'rooms_4_5';
	case Rooms5_0 = 'rooms_5_0';
	case Rooms5_5 = 'rooms_5_5';

	public function label(): string
	{
		return match ($this) {
			self::Rooms2_0 => '2',
			self::Rooms2_5 => '2½',
			self::Rooms3_0 => '3',
			self::Rooms3_5 => '3½',
			self::Rooms4_0 => '4',
			self::Rooms4_5 => '4½',
			self::Rooms5_0 => '5',
			self::Rooms5_5 => '5½',
		};
	}

	public function size(): float
	{
		return match ($this) {
			self::Rooms2_0 => 2.0,
			self::Rooms2_5 => 2.5,
			self::Rooms3_0 => 3.0,
			self::Rooms3_5 => 3.5,
			self::Rooms4_0 => 4.0,
			self::Rooms4_5 => 4.5,
			self::Rooms5_0 => 5.0,
			self::Rooms5_5 => 5.5,
		};
	}

	public function sortOrder(): int
	{
		return (int) ($this->size() * 10);
	}

	public function active(): bool
	{
		return true;
	}
}
