<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\SecuredLinks;

use Nette;
use Nette\Application\Application;
use Nette\Application\IResponse;
use Nette\Application\Responses\RedirectResponse;


class RedirectChecker
{
	/**
	 * @param  Application $app
	 * @param  IResponse   $response
	 * @return void
	 */
	public function checkResponse(Application $app, IResponse $response)
	{
		$requests = $app->getRequests();
		$request = $requests[count($requests) - 1];

		if ($request->hasFlag(SecuredRouter::SIGNED) && !$response instanceof RedirectResponse) {
			throw new \LogicException('Secured request did not redirect. Possible CSRF-token reveal by HTTP referer header.');
		}
	}
}
