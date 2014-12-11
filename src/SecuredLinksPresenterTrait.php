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
use Nette\Application\UI\PresenterComponent;


trait SecuredLinksPresenterTrait
{
	use SecuredLinksControlTrait;


	/**
	 * @param  PresenterComponent $component
	 * @param  string $link created URL
	 * @param  string $destination
	 * @return string
	 * @throws Nette\Application\UI\InvalidLinkException
	 */
	public function createSecuredLink(PresenterComponent $component, $link, $destination)
	{
		/** @var $lastRequest Nette\Application\Request */
		$lastRequest = $this->lastCreatedRequest;

		do {
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

			$a = strpos($destination, '//');
			if ($a !== FALSE) {
				$destination = substr($destination, $a + 2);
			}

			$signal = strtr(rtrim($destination, '!'), ':', '-');
			$a = strrpos($signal, '-');
			if ($a !== FALSE) {
				if ($component instanceof Nette\Application\UI\Presenter && substr($destination, -1) !== '!') {
					break;
				}

				$component = $component->getComponent(substr($signal, 0, $a));
				$signal = (string) substr($signal, $a + 1);
			}

			if ($signal == NULL) { // intentionally ==
				throw new Nette\Application\UI\InvalidLinkException('Signal must be non-empty string.');
			}

			// only PresenterComponent
			if (!$component instanceof PresenterComponent) {
				break;
			}

			$reflection = $component->getReflection();
			$method = $component->formatSignalMethod($signal);
			$signalReflection = $reflection->getMethod($method);

			if (!$signalReflection->hasAnnotation('secured')) {
				break;
			}

			$origParams = $lastRequest->getParameters();
			$protectedParams = array($component->getUniqueId());
			foreach ($signalReflection->getParameters() as $param) {
				if ($param->isOptional()) {
					continue;
				}
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

			$link .= (strpos($link, '?') !== FALSE ? '&' : '?') . $component->getParameterId('_sec') . '=' . $protectedParam . $fragment;
		} while (FALSE);

		return $link;
	}


	/**
	 * Returns unique token for method and params
	 * @param  string $control
	 * @param  string $method
	 * @param  array $params
	 * @return string
	 */
	public function getCsrfToken($control, $method, $params)
	{
		$session = $this->getSession('Nextras.Application.UI.SecuredLinksPresenterTrait');
		if (!isset($session->token)) {
			$session->token = Nette\Utils\Random::generate();
		}

		$params = Nette\Utils\Arrays::flatten($params);
		$params = implode('|', array_keys($params)) . '|' . implode('|', array_values($params));
		return substr(md5($control . $method . $params . $session->token . $this->getSession()->getId()), 0, 8);
	}

}
