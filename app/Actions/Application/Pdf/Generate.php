<?php

namespace App\Actions\Application\Pdf;

use App\Models\Application;
use Illuminate\Support\Collection;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\LaravelPdf\Facades\Pdf;

class Generate
{
	/**
	 * Render a selection of applications into a single PDF and store it on the
	 * given disk (used by the app:export-pdf command for local previews).
	 *
	 * @param  Collection<int, Application>	 $applications
	 */
	public function execute(Collection $applications, string $disk, string $path): void
	{
		$this->build($applications)->disk($disk)->save($path);
	}

	/**
	 * Render the same PDF as a streamed download response, for the synchronous
	 * export endpoint (a handful of selected applications — the common case).
	 *
	 * @param  Collection<int, Application>	 $applications
	 */
	public function download(Collection $applications, string $filename): PdfBuilder
	{
		return $this->build($applications)->download($filename);
	}

	/**
	 * Build the configured PDF — one application per cover page (page breaks in the
	 * Blade view). Browsershot renders locally in dev; production offloads to the
	 * Sidecar Lambda function (avoids needing Chrome on the crontab-only host).
	 *
	 * Per-page numbers come from the Browsershot footer (pdf.footer), which Chrome
	 * fills via the pageNumber/totalPages spans. The bottom margin matches the
	 * \@page bottom in _styles.blade.php so content never collides with the footer.
	 *
	 * @param  Collection<int, Application>	 $applications
	 */
	private function build(Collection $applications): PdfBuilder
	{
		$present = new Present;

		$data = [
			'applications' => $applications
				->map(fn ($application) => $present->execute($application))
				->all(),
			'fonts' => Assets::fonts(),
			'generatedAt' => now(),
		];

		$pdf = Pdf::view('pdf.applications', $data)
			->format('a4')
			->margins(12, 15, 16, 15)
			->footerView('pdf.footer', ['fonts' => Assets::fonts()]);

		if (app()->isProduction()) {
			$pdf->onLambda();
		}

		return $pdf;
	}
}
