<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('applications', function (Blueprint $table) {
			$table->id();
			$table->unsignedInteger('reference_number')->unique();
			$table->string('status', 20)->index();
			$table->boolean('flagged')->default(false);
			$table->dateTime('opened_at');
			$table->dateTime('extended_at')->nullable();
			$table->dateTime('archived_at')->nullable();
			$table->dateTime('last_changed_at');
			$table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
			$table->boolean('shares_apartment')->default(false);
			$table->string('submitted_ip', 45)->nullable();
			$table->string('submitted_user_agent', 512)->nullable();
			$table->string('submission_id', 36)->nullable()->unique();
			$table->timestamps();
			$table->softDeletes();

			$table->index(['opened_at', 'status']);
		});

		// Reference number sequence start (MySQL only). SQLite/tests start from 1.
		if (DB::connection()->getDriverName() === 'mysql') {
			$start = (int) config('aporta.reference_number_start', 1);
			// Drop the unique index temporarily-not needed; reference_number is a plain int with a unique index,
			// so we simply allocate it via a DB sequence implemented as an auxiliary AUTO_INCREMENT counter.
			// Simpler: create a helper table whose AUTO_INCREMENT seeds the sequence.
			DB::statement('CREATE TABLE application_reference_seq (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB');
			if ($start > 1) {
				DB::statement("ALTER TABLE application_reference_seq AUTO_INCREMENT = {$start}");
			}
		} else {
			Schema::create('application_reference_seq', function (Blueprint $table) {
				$table->id();
			});
		}
	}

	public function down(): void
	{
		Schema::dropIfExists('application_reference_seq');
		Schema::dropIfExists('applications');
	}
};
