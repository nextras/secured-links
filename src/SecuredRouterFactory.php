<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\SecuredLinks;

use Nette;
use Nette\Application\IRouter;


interface SecuredRouterFactory
{
	/**
	 * @param  IRouter $innerRouter
	 * @return SecuredRouter
	 */
	public function create(IRouter $innerRouter);
}
