<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('household_info', function (Blueprint $table) {
			$table->id();
			$table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
			$table->unsignedTinyInteger('total_persons');
			$table->unsignedTinyInteger('adults_count');
			$table->unsignedTinyInteger('children_count')->default(0);
			$table->boolean('all_children_live_constantly')->nullable();
			$table->boolean('plays_music');
			$table->string('musical_instruments', 200)->nullable();
			$table->boolean('has_pets');
			$table->string('pets_description', 200)->nullable();
			$table->text('remarks')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('household_info');
	}
};
