<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 * @author     Jan Skrasek
 */

namespace Nextras\Application\UI;

use Nette;
use Nette\Application\UI\PresenterComponentReflection;



trait SecuredLinksPresenterTrait
{

	use SecuredLinksControlTrait;



	public function createSecuredLink($component, $link, $destination)
	{
		/** @var $lastRequest Nette\Application\Request */
		$lastRequest = $this->lastCreatedRequest;

		while (TRUE) {
			if ($lastRequest === NULL) {
				break;
			}

			$params = $lastRequest->getParameters();
			if (!isset($params[Nette\Application\UI\Presenter::SIGNAL_KEY])) {
				break;
			}

			if (($pos = strpos($destination, '#')) !== FALSE) {
				$destination = substr($destination, 0, $pos);
			}

			$signal = rtrim($destination, '!');
			$a = strrpos($signal, ':');
			if ($a !== FALSE) {
				$component = $component->getComponent(strtr(substr($signal, 0, $a), ':', '-'));
				$signal = (string) substr($signal, $a + 1);
			}
			if ($signal == NULL) { // intentionally ==
				throw new InvalidLinkException("Signal must be non-empty string.");
			}

			// only PresenterComponent - is it really needed?
			if (!$component instanceof Nette\Application\UI\PresenterComponent) {
				break;
			}

			$reflection = new PresenterComponentReflection(get_class($component));
			$method = $component->formatSignalMethod($signal);
			$signalReflection = $reflection->getMethod($method);

			if (!$signalReflection->hasAnnotation('secured')) {
				break;
			}

			$origParams = $lastRequest->getParameters();
			$protectedParams = array($component->getUniqueId());
			foreach ($signalReflection->getParameters() as $param) {
				if (isset($origParams[$component->getParameterId($param->name)])) {
					$protectedParams[$param->name] = $origParams[$component->getParameterId($param->name)];
				}
			}

			$protectedParam = $this->getCsrfToken(get_class($component), $method, $protectedParams);

			if (($pos = strpos($link, '#')) === FALSE) {
				$fragment = '';
			} else {
				$fragment = substr($link, $pos);
				$link = substr($link, 0, $pos);
			}

			// link already contain do param
			$link .= '&' . $component->getParameterId('_sec') . '=' . $protectedParam . $fragment;
			break;
		}

		return $link;
	}



	/**
	 * Returns unique token for method and params
	 * @param string
	 * @param string
	 * @param array
	 * @return string
	 */
	public function getCsrfToken($control, $method, $params)
	{
		$session = $this->getSession('Nette.Application.UI.Presenter/CSRF');
		if (!isset($session->token)) {
			$session->token = Nette\Utils\Strings::random();
		}

		$params = Nette\Utils\Arrays::flatten($params);
		$params = implode('|', array_keys($params)) . '|' . implode('|', array_values($params));
		return substr(md5($control . $method . $params . $session->token), 0, 8);
	}

}
