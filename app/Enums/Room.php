<?php

namespace App\Enums;

enum Room: string implements LabeledEnum
{
	case Rooms2_0 = 'rooms_2_0';
	case Rooms3_0 = 'rooms_3_0';
	case Rooms4_0 = 'rooms_4_0';
	case Rooms5_0 = 'rooms_5_0';
	case Rooms6_0 = 'rooms_6_0';

	public function label(): string
	{
		return match ($this) {
			self::Rooms2_0 => '2',
			self::Rooms3_0 => '3',
			self::Rooms4_0 => '4',
			self::Rooms5_0 => '5',
			self::Rooms6_0 => '6',
		};
	}

	public function size(): float
	{
		return match ($this) {
			self::Rooms2_0 => 2.0,
			self::Rooms3_0 => 3.0,
			self::Rooms4_0 => 4.0,
			self::Rooms5_0 => 5.0,
			self::Rooms6_0 => 6.0,
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

	/**
	 * The whole-room sizes a household of this size may occupy. Rooms are not a
	 * free choice: the eligible range is persons ± 1, in whole rooms only,
	 * clamped to the available stock (2..6). Examples: 1 → [2], 3 → [2,3,4],
	 * 5 → [4,5,6], 6 → [5,6].
	 *
	 * @return array<int, self>
	 */
	public static function rangeForPersons(int $persons): array
	{
		$min = max(2, min($persons - 1, 6));
		$max = max(2, min($persons + 1, 6));

		return array_values(array_filter(
			self::cases(),
			fn (self $room) => $room->size() >= $min && $room->size() <= $max,
		));
	}

	/**
	 * The derived room range as slugs, ready to write to the pivot.
	 *
	 * @return array<int, string>
	 */
	public static function slugsForPersons(int $persons): array
	{
		return array_map(fn (self $room) => $room->value, self::rangeForPersons($persons));
	}
}
