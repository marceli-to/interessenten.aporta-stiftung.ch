<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy phone fields hold free text — second numbers, contact names, even whole
 * sentences (landlord_phone runs up to 118 chars). Widen the phone columns so the
 * messy historical values import whole instead of being truncated/rejected.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->string('mobile_phone', 50)->nullable()->change();
			$table->string('landline_phone', 50)->nullable()->change();
		});

		Schema::table('current_housings', function (Blueprint $table) {
			$table->string('landlord_phone', 255)->nullable()->change();
		});
	}

	public function down(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->string('mobile_phone', 30)->nullable()->change();
			$table->string('landline_phone', 30)->nullable()->change();
		});

		Schema::table('current_housings', function (Blueprint $table) {
			$table->string('landlord_phone', 30)->nullable()->change();
		});
	}
};
