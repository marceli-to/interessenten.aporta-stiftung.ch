<?php

require_once __DIR__.'/Helpers.php';

pest()->extend(Tests\TestCase::class)
	->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
	->in('Feature');

expect()->extend('toBeOne', function () {
	return $this->toBe(1);
});
