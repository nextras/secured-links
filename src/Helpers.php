<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\Application\UI;

use Nette;
use Nette\Http\Session;


class Helpers
{
	/**
	 * Returns unique token for method and params
	 * @param  Session $session
	 * @param  string  $controlName
	 * @param  string  $method
	 * @param  array   $params
	 * @return string
	 */
	public static function getCsrfToken(Session $session, $controlName, $method, array $params)
	{
		$sessionSection = $session->getSection('Nextras.Application.UI.SecuredLinksPresenterTrait');
		if (!isset($sessionSection->token)) {
			$sessionSection->token = function_exists('random_bytes')
				? random_bytes(16)
				: Nette\Utils\Random::generate(16, "\x00-\xFF");
		}

		$params = Nette\Utils\Arrays::flatten($params);
		$params = implode('|', array_keys($params)) . '|' . implode('|', array_values($params));
		return substr(md5($controlName . $method . $params . $sessionSection->token . $session->getId()), 0, 8);
	}
}
