<?php

namespace Database\Seeders;

use Database\Factories\ApplicantFactory;
use Database\Factories\ApplicationFactory;
use Database\Factories\CurrentHousingFactory;
use Database\Factories\EmployerFactory;
use Illuminate\Database\Seeder;

class DevDataSeeder extends Seeder
{
	public function run(): void
	{
		ApplicationFactory::new()
			->count(8)
			->has(
				ApplicantFactory::new()
					->mainApplicant()
					->has(EmployerFactory::new(), 'employer')
					->has(CurrentHousingFactory::new(), 'currentHousing'),
				'applicants',
			)
			->create();
	}
}
