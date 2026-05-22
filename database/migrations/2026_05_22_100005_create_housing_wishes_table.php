<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('housing_wishes', function (Blueprint $table) {
			$table->id();
			$table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
			$table->boolean('wants_balcony')->nullable();
			$table->boolean('wants_elevator')->nullable();
			$table->decimal('max_gross_rent', 8, 2);
			$table->date('earliest_move_in');
			$table->string('property_group', 50)->nullable();
			$table->string('property_class', 50)->nullable();
			$table->timestamps();
		});

		Schema::create('housing_wish_districts', function (Blueprint $table) {
			$table->foreignId('housing_wish_id')->constrained()->cascadeOnDelete();
			$table->string('district_slug', 20);
			$table->primary(['housing_wish_id', 'district_slug']);
		});

		Schema::create('housing_wish_floors', function (Blueprint $table) {
			$table->foreignId('housing_wish_id')->constrained()->cascadeOnDelete();
			$table->string('floor_slug', 20);
			$table->primary(['housing_wish_id', 'floor_slug']);
		});

		Schema::create('housing_wish_rooms', function (Blueprint $table) {
			$table->foreignId('housing_wish_id')->constrained()->cascadeOnDelete();
			$table->string('room_slug', 20);
			$table->primary(['housing_wish_id', 'room_slug']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('housing_wish_rooms');
		Schema::dropIfExists('housing_wish_floors');
		Schema::dropIfExists('housing_wish_districts');
		Schema::dropIfExists('housing_wishes');
	}
};
