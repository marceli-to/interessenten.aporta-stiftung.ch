<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			if (Schema::hasColumn('users', 'role')) {
				$table->dropColumn('role');
			}

			if (Schema::hasColumn('users', 'active')) {
				$table->dropColumn('active');
			}
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->enum('role', ['admin', 'editor', 'viewer'])->default('admin')->after('email');
			$table->boolean('active')->default(true)->after('role');
		});
	}
};
