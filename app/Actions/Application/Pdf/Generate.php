<?php

namespace App\Actions\Application\Pdf;

use Illuminate\Support\Collection;
use Spatie\LaravelPdf\Facades\Pdf;

class Generate
{
	/**
	 * Render a selection of applications into a single PDF and store it on the
	 * given disk. One application per page (page-break is handled in the Blade
	 * view). Browsershot renders locally in dev; production offloads to the
	 * Sidecar Lambda function (avoids needing Chrome on the crontab-only host).
	 *
	 * Pure render + persist — id resolution, eager loading and status tracking
	 * stay in the GeneratePdf job so this can be unit-tested with Pdf::fake().
	 *
	 * @param  Collection<int, \App\Models\Application>  $applications
	 */
	public function execute(Collection $applications, string $disk, string $path): void
	{
		$pdf = Pdf::view('pdf.applications', ['applications' => $applications])
			->format('a4');

		if (app()->isProduction()) {
			$pdf->onLambda();
		}

		$pdf->disk($disk)->save($path);
	}
}
