<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('application_exports', function (Blueprint $table) {
			$table->id();
			$table->string('status', 20)->default('pending');
			$table->string('disk', 40)->nullable();
			$table->string('path', 255)->nullable();
			$table->unsignedInteger('application_count')->nullable();
			$table->text('failure_reason')->nullable();
			$table->dateTime('expires_at')->nullable();

			$table->foreignId('user_id')->constrained()->cascadeOnDelete();

			$table->timestamps();

			$table->index(['user_id', 'status']);
			$table->index('expires_at');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('application_exports');
	}
};
