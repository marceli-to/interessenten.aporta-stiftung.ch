<?php

namespace App\Enums;

interface LabeledEnum
{
	public function label(): string;

	public function sortOrder(): int;

	public function active(): bool;
}
