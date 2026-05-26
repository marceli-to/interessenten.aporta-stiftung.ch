<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('notes', function (Blueprint $table) {
			$table->id();
			$table->text('body');
			$table->boolean('important')->default(false);

			$table->foreignId('application_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained('users');

			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('notes');
	}
};
