<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notes can be title-only: 265 of the 760 legacy notes have no body text. Relaxing
 * `body` to nullable lets those import as a title with a null body instead of an
 * empty string. App-created notes still require a body (enforced in StoreRequest).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('notes', function (Blueprint $table) {
			$table->text('body')->nullable()->change();
		});
	}

	public function down(): void
	{
		Schema::table('notes', function (Blueprint $table) {
			$table->text('body')->nullable(false)->change();
		});
	}
};
