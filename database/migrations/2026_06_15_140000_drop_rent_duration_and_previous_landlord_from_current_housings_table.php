<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('current_housings', function (Blueprint $table) {
			$table->dropColumn(['rent_duration_slug', 'previous_landlord']);
		});
	}

	public function down(): void
	{
		Schema::table('current_housings', function (Blueprint $table) {
			$table->string('rent_duration_slug', 30)->after('landlord_phone');
			$table->string('previous_landlord', 200)->nullable()->after('rent_duration_slug');
		});
	}
};
