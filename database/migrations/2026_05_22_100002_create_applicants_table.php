<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('applicants', function (Blueprint $table) {
			$table->id();
			$table->string('role', 20);
			$table->smallInteger('position');
			$table->string('salutation', 10);
			$table->string('first_name', 100);
			$table->string('last_name', 100);
			$table->string('street', 200)->nullable();
			$table->string('street_number', 20)->nullable();
			$table->string('postal_code', 10)->nullable();
			$table->string('city', 100)->nullable();
			$table->boolean('same_address_as_main')->nullable();
			$table->date('birth_date');
			$table->string('marital_status', 30);
			$table->char('nationality', 2)->index();
			$table->string('place_of_origin', 100)->nullable();
			$table->string('residence_permit', 5)->nullable();
			$table->date('swiss_residence_since')->nullable();
			$table->string('mobile_phone', 30);
			$table->string('landline_phone', 30)->nullable();
			$table->string('email', 255);
			$table->string('occupation', 200);
			$table->string('employment_status', 30);
			$table->boolean('debt_enforcement_last_2y');
			$table->string('relationship_to_main', 30)->nullable();

			$table->foreignId('application_id')->constrained()->cascadeOnDelete();

			$table->timestamps();

			$table->unique(['application_id', 'position']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('applicants');
	}
};
