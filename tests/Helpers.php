<?php

if (! function_exists('laafifFixture')) {
	function laafifFixture(): array
	{
		return json_decode(file_get_contents(base_path('tests/Fixtures/laafif.json')), true);
	}
}
