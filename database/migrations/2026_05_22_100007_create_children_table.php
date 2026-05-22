<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('children', function (Blueprint $table) {
			$table->id();
			$table->foreignId('application_id')->constrained()->cascadeOnDelete();
			$table->smallInteger('position');
			$table->smallInteger('birth_year');
			$table->timestamps();

			$table->unique(['application_id', 'position']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('children');
	}
};
