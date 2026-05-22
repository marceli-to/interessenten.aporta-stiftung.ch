<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('current_housings', function (Blueprint $table) {
			$table->id();
			$table->foreignId('applicant_id')->unique()->constrained()->cascadeOnDelete();
			$table->string('tenant_role', 20);
			$table->boolean('terminated_by_landlord');
			$table->text('termination_reason')->nullable();
			$table->string('landlord_name', 200);
			$table->string('landlord_contact_person', 200)->nullable();
			$table->string('landlord_phone', 30)->nullable();
			$table->string('rent_duration_slug', 30);
			$table->string('previous_landlord', 200)->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('current_housings');
	}
};
