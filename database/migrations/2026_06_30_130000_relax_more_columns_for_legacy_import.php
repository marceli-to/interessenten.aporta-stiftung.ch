<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * More columns the legacy export can't always fill (docs/legacy-import.md). Same
 * call as the applicant contact fields: relax to nullable so historical rows import
 * as-is; the live intake still requires them at the request layer.
 *
 *   applicants.marital_status    empty/code 0  (~11)
 *   applicants.employment_status empty         (~5)
 *   current_housings.tenant_role         code 0/empty with a landlord present (~4)
 *   current_housings.landlord_name       missing (~3)
 *   current_housings.terminated_by_landlord  TERMINATOR can be empty/ambiguous
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->string('marital_status', 30)->nullable()->change();
			$table->string('employment_status', 30)->nullable()->change();
		});

		Schema::table('current_housings', function (Blueprint $table) {
			$table->string('tenant_role', 20)->nullable()->change();
			$table->string('landlord_name', 200)->nullable()->change();
			$table->boolean('terminated_by_landlord')->nullable()->change();
		});
	}

	public function down(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->string('marital_status', 30)->nullable(false)->change();
			$table->string('employment_status', 30)->nullable(false)->change();
		});

		Schema::table('current_housings', function (Blueprint $table) {
			$table->string('tenant_role', 20)->nullable(false)->change();
			$table->string('landlord_name', 200)->nullable(false)->change();
			$table->boolean('terminated_by_landlord')->nullable(false)->change();
		});
	}
};
