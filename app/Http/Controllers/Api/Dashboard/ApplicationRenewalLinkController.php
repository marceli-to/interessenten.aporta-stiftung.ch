<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\SendRenewalLink;
use App\Actions\Application\Show as ShowApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationDetailResource;
use App\Models\Application;

class ApplicationRenewalLinkController extends Controller
{
	/**
	 * Send the renewal link to the main applicant (after they got in touch).
	 * Returns the fresh detail so the panel shows the new "Link gesendet" date.
	 */
	public function store(Application $application, SendRenewalLink $sendRenewalLink)
	{
		$sendRenewalLink->execute($application);

		return new ApplicationDetailResource(
			(new ShowApplication())->execute($application)
		);
	}
}
