<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('status_events', function (Blueprint $table) {
			$table->id();
			$table->string('from_status', 20)->nullable();
			$table->string('to_status', 20);
			$table->dateTime('occurred_at');
			$table->string('reason', 255)->nullable();

			$table->foreignId('application_id')->constrained()->cascadeOnDelete();
			$table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

			$table->index(['application_id', 'occurred_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('status_events');
	}
};
