<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('applications', function (Blueprint $table) {
			$table->dropColumn(['wants_balcony', 'wants_elevator']);
		});
	}

	public function down(): void
	{
		Schema::table('applications', function (Blueprint $table) {
			$table->boolean('wants_balcony')->nullable()->after('shares_apartment');
			$table->boolean('wants_elevator')->nullable()->after('wants_balcony');
		});
	}
};
