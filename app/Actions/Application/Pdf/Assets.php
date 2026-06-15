<?php

namespace App\Actions\Application\Pdf;

class Assets
{
	/**
	 * The Segment webfonts, embedded as base64 data URIs so the PDF renders
	 * reproducibly without a Vite/asset pipeline. Shared by the real export
	 * (Pdf\Generate) and the dev preview (_preview_data.php) so the embedded
	 * fonts can never drift between the two.
	 *
	 * @return array{regular: string, medium: string, bold: string}
	 */
	public static function fonts(): array
	{
		$dir = resource_path('fonts');

		$uri = function (string $file) use ($dir): string {
			$path = $dir.'/'.$file;

			return is_file($path)
				? 'data:font/woff2;base64,'.base64_encode((string) file_get_contents($path))
				: '';
		};

		return [
			'regular' => $uri('Segment-Regular.woff2'),
			'medium' => $uri('Segment-Medium.woff2'),
			'bold' => $uri('Segment-Bold.woff2'),
		];
	}
}
