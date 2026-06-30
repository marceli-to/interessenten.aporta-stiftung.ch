<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy applications sometimes lack fields the public intake form requires — a
 * named second applicant with no e-mail/phone/occupation/birthdate (~18 records),
 * see docs/legacy-import.md. Relax those columns to nullable so the historical data
 * imports as-is. The live intake form still requires them (enforced in the
 * Statamic-side request + StoreRequest), so this only loosens storage, not input.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->date('birth_date')->nullable()->change();
			$table->string('mobile_phone', 30)->nullable()->change();
			$table->string('email', 255)->nullable()->change();
			$table->string('occupation', 200)->nullable()->change();
		});
	}

	public function down(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->date('birth_date')->nullable(false)->change();
			$table->string('mobile_phone', 30)->nullable(false)->change();
			$table->string('email', 255)->nullable(false)->change();
			$table->string('occupation', 200)->nullable(false)->change();
		});
	}
};
