<?php

namespace App\Enums;

enum IncomeBracket: string implements LabeledEnum
{
	case LessThan20k = 'less_than_20k';
	case From20kTo30k = '20k_30k';
	case From30kTo40k = '30k_40k';
	case From40kTo50k = '40k_50k';
	case From50kTo60k = '50k_60k';
	case From60kTo70k = '60k_70k';
	case From70kTo80k = '70k_80k';
	case From80kTo90k = '80k_90k';
	case From90kTo100k = '90k_100k';
	case From100kTo120k = '100k_120k';
	case From120kTo140k = '120k_140k';
	case MoreThan140k = 'more_than_140k';

	public function label(): string
	{
		return match ($this) {
			self::LessThan20k => "Weniger als 20'000",
			self::From20kTo30k => "20'000–30'000",
			self::From30kTo40k => "30'000–40'000",
			self::From40kTo50k => "40'000–50'000",
			self::From50kTo60k => "50'000–60'000",
			self::From60kTo70k => "60'000–70'000",
			self::From70kTo80k => "70'000–80'000",
			self::From80kTo90k => "80'000–90'000",
			self::From90kTo100k => "90'000–100'000",
			self::From100kTo120k => "100'000–120'000",
			self::From120kTo140k => "120'000–140'000",
			self::MoreThan140k => "Mehr als 140'000",
		};
	}

	public function shortLabel(): string
	{
		return match ($this) {
			self::LessThan20k => '< 20k',
			self::MoreThan140k => '> 140k',
			default => str_replace('_', '–', $this->value),
		};
	}

	public function sortOrder(): int
	{
		return match ($this) {
			self::LessThan20k => 1,
			self::From20kTo30k => 2,
			self::From30kTo40k => 3,
			self::From40kTo50k => 4,
			self::From50kTo60k => 5,
			self::From60kTo70k => 6,
			self::From70kTo80k => 7,
			self::From80kTo90k => 8,
			self::From90kTo100k => 9,
			self::From100kTo120k => 10,
			self::From120kTo140k => 11,
			self::MoreThan140k => 12,
		};
	}

	public function active(): bool
	{
		return true;
	}

	/**
	 * The bracket slugs whose ordered position falls within [min, max], where each
	 * bound is a bracket slug (either may be null/unknown for an open end). Used by
	 * the list filter to turn a "from bracket – to bracket" range into a concrete
	 * IN (...) set, so the ordering lives here with sortOrder() rather than in SQL.
	 * Returns an empty array when the range is inverted (min above max).
	 */
	public static function slugsInRange(?string $minSlug, ?string $maxSlug): array
	{
		$min = self::tryFrom($minSlug ?? '')?->sortOrder() ?? PHP_INT_MIN;
		$max = self::tryFrom($maxSlug ?? '')?->sortOrder() ?? PHP_INT_MAX;

		return collect(self::cases())
			->filter(fn (self $bracket) => $bracket->sortOrder() >= $min && $bracket->sortOrder() <= $max)
			->map(fn (self $bracket) => $bracket->value)
			->values()
			->all();
	}
}
