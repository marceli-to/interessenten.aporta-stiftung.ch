<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two yes/no facts the legacy export occasionally leaves blank — debt enforcement
 * (4 applicants) and pets (1 application). Relax to nullable so "unknown" stays
 * null rather than being fabricated as false. Live intake still requires them.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->boolean('debt_enforcement_last_2y')->nullable()->change();
		});

		Schema::table('applications', function (Blueprint $table) {
			$table->boolean('has_pets')->nullable()->change();
		});
	}

	public function down(): void
	{
		Schema::table('applicants', function (Blueprint $table) {
			$table->boolean('debt_enforcement_last_2y')->nullable(false)->change();
		});

		Schema::table('applications', function (Blueprint $table) {
			$table->boolean('has_pets')->nullable(false)->change();
		});
	}
};
