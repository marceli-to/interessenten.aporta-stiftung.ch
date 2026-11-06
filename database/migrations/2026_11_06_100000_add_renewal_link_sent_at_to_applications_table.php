<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('applications', function (Blueprint $table) {
			// When the office last sent the renewal link in the current validity
			// cycle. Reset to null whenever extended_at changes (see
			// Application::booted): a renewal uses the link up.
			$table->dateTime('renewal_link_sent_at')->nullable()->after('archived_at');
		});
	}

	public function down(): void
	{
		Schema::table('applications', function (Blueprint $table) {
			$table->dropColumn('renewal_link_sent_at');
		});
	}
};
