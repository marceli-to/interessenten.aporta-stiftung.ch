<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notes gain an optional title shown above the body. App-created notes leave it
 * null (the form collects body only); the legacy import populates it from the old
 * note `title` field (see docs/legacy-import.md).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('notes', function (Blueprint $table) {
			$table->string('title', 200)->nullable()->after('id');
		});
	}

	public function down(): void
	{
		Schema::table('notes', function (Blueprint $table) {
			$table->dropColumn('title');
		});
	}
};
