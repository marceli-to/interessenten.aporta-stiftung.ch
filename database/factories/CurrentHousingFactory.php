<?php

namespace Database\Factories;

use App\Enums\RentDuration;
use App\Enums\TenantRole;
use App\Models\CurrentHousing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrentHousing>
 */
class CurrentHousingFactory extends Factory
{
	protected $model = CurrentHousing::class;

	public function definition(): array
	{
		return [
			'tenant_role' => TenantRole::MainTenant,
			'terminated_by_landlord' => false,
			'termination_reason' => null,
			'landlord_name' => fake()->company(),
			'landlord_contact_person' => fake()->name(),
			'landlord_phone' => '+41442982047',
			'rent_duration_slug' => RentDuration::MoreThan2Years->value,
			'previous_landlord' => null,
		];
	}
}
