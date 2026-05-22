<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNormalizer
{
	public function normalize(?string $raw, string $defaultRegion = 'CH'): ?string
	{
		if ($raw === null) {
			return null;
		}

		$trimmed = trim($raw);
		if ($trimmed === '') {
			return null;
		}

		$util = PhoneNumberUtil::getInstance();

		try {
			$number = $util->parse($trimmed, $defaultRegion);
		} catch (NumberParseException) {
			return $trimmed;
		}

		if (! $util->isValidNumber($number)) {
			return $trimmed;
		}

		return $util->format($number, PhoneNumberFormat::E164);
	}

	public function isValid(?string $raw, string $defaultRegion = 'CH'): bool
	{
		if ($raw === null || trim($raw) === '') {
			return false;
		}

		$util = PhoneNumberUtil::getInstance();

		try {
			$number = $util->parse(trim($raw), $defaultRegion);
		} catch (NumberParseException) {
			return false;
		}

		return $util->isValidNumber($number);
	}
}
