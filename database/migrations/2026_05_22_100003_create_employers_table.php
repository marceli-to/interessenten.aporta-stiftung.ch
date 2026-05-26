<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('employers', function (Blueprint $table) {
			$table->id();
			$table->string('name', 200);
			$table->unsignedTinyInteger('workload_percent');
			$table->string('annual_income_bracket_slug', 20);

			$table->foreignId('applicant_id')->unique()->constrained()->cascadeOnDelete();

			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('employers');
	}
};
