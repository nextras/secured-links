<?php declare(strict_types=1);

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
	 * Sets token's expiration
	 */
	public static function setCsrfTokenExpiration(Session $session, $time)
	{
		$sessionSection = $session->getSection('Nextras.Application.UI.SecuredLinksPresenterTrait');
		$sessionSection->setExpiration($time);
	}


	/**
	 * Returns unique token for method and params
	 */
	public static function getCsrfToken(Session $session, string $controlClassName, string $method, array $params): string
	{
		$sessionSection = $session->getSection('Nextras.Application.UI.SecuredLinksPresenterTrait');
		if (!isset($sessionSection->token)) {
			$sessionSection->token = function_exists('random_bytes')
				? random_bytes(16)
				: Nette\Utils\Random::generate(16, "\x00-\xFF");
		}

		$params = Nette\Utils\Arrays::flatten($params);
		$params = implode('|', array_keys($params)) . '|' . implode('|', array_values($params));

		$data = $controlClassName . $method . $params . $session->getId();
		$hash = hash_hmac('sha1', $data, $sessionSection->token, TRUE);
		$token = strtr(substr(base64_encode($hash), 0, 8), '+/', '-_');

		return $token;
	}
}
