<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('application_districts', function (Blueprint $table) {
			$table->foreignId('application_id')->constrained()->cascadeOnDelete();
			$table->string('district_slug', 20);
			$table->primary(['application_id', 'district_slug']);
		});

		Schema::create('application_floors', function (Blueprint $table) {
			$table->foreignId('application_id')->constrained()->cascadeOnDelete();
			$table->string('floor_slug', 20);
			$table->primary(['application_id', 'floor_slug']);
		});

		Schema::create('application_rooms', function (Blueprint $table) {
			$table->foreignId('application_id')->constrained()->cascadeOnDelete();
			$table->string('room_slug', 20);
			$table->primary(['application_id', 'room_slug']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('application_rooms');
		Schema::dropIfExists('application_floors');
		Schema::dropIfExists('application_districts');
	}
};
