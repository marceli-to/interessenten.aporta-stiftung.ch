<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\District;
use App\Enums\EmploymentStatus;
use App\Enums\Floor;
use App\Enums\IncomeBracket;
use App\Enums\LabeledEnum;
use App\Enums\MaritalStatus;
use App\Enums\Nationality;
use App\Enums\RelationshipToMain;
use App\Enums\RentDuration;
use App\Enums\ResidencePermit;
use App\Enums\Room;
use App\Enums\Salutation;
use App\Enums\Status;
use App\Enums\TenantRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController
{
	/**
	 * GET /api/v1/lookups
	 *
	 * Returns every reference set as a flat list of `{ slug, label, sort_order, active }`.
	 * `rooms` adds `size`. Append-only contract — slugs are never renamed.
	 */
	public function show(Request $request): JsonResponse
	{
		$payload = [
			'statuses' => $this->fromEnum(Status::class),
			'salutations' => $this->fromEnum(Salutation::class),
			'marital_statuses' => $this->fromEnum(MaritalStatus::class),
			'employment_statuses' => $this->fromEnum(EmploymentStatus::class),
			'residence_permits' => $this->fromEnum(ResidencePermit::class),
			'relationships' => $this->fromEnum(RelationshipToMain::class),
			'tenant_roles' => $this->fromEnum(TenantRole::class),
			'districts' => $this->fromEnum(District::class),
			'floors' => $this->fromEnum(Floor::class),
			'rooms' => $this->roomsList(),
			'income_brackets' => $this->fromEnum(IncomeBracket::class),
			'rent_durations' => $this->fromEnum(RentDuration::class),
			'nationalities' => $this->fromEnum(Nationality::class),
		];

		$etag = '"'.md5(config('app.version', 'dev').'|'.$this->enumsFingerprint()).'"';

		if ($request->headers->get('If-None-Match') === $etag) {
			return response()->json(null, 304)
				->header('ETag', $etag)
				->header('Cache-Control', 'public, no-cache');
		}

		return response()->json($payload)
			->header('ETag', $etag)
			->header('Cache-Control', 'public, no-cache')
			->header('Vary', 'Accept-Language');
	}

	/**
	 * @param  class-string<LabeledEnum&\BackedEnum>  $enum
	 * @return array<int, array{slug:string, label:string, sort_order:int, active:bool}>
	 */
	private function fromEnum(string $enum): array
	{
		return collect($enum::cases())
			->map(fn (LabeledEnum $case) => [
				'slug' => $case->value,
				'label' => $case->label(),
				'sort_order' => $case->sortOrder(),
				'active' => $case->active(),
			])
			->sortBy('sort_order')
			->values()
			->all();
	}

	/**
	 * @return array<int, array{slug:string, label:string, sort_order:int, active:bool, size:float}>
	 */
	private function roomsList(): array
	{
		return collect(Room::cases())
			->map(fn (Room $case) => [
				'slug' => $case->value,
				'label' => $case->label(),
				'sort_order' => $case->sortOrder(),
				'active' => $case->active(),
				'size' => $case->size(),
			])
			->sortBy('sort_order')
			->values()
			->all();
	}

	private function enumsFingerprint(): string
	{
		$files = glob(app_path('Enums').'/*.php') ?: [];
		$mtimes = array_map(fn ($file) => (string) filemtime($file), $files);

		return md5(implode('|', $mtimes));
	}
}
