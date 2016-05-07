<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\Application\UI;

use Nette;
use Nette\Application\UI\Component;


trait SecuredLinksPresenterTrait
{
	use SecuredLinksControlTrait;


	/**
	 * @param  Component $component
	 * @param  string    $link created URL
	 * @param  string    $destination
	 * @return string
	 * @throws Nette\Application\UI\InvalidLinkException
	 */
	public function createSecuredLink(Component $component, $link, $destination)
	{
		/** @var $lastRequest Nette\Application\Request */
		$lastRequest = $this->getLastCreatedRequest();

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

			// only Component
			if (!$component instanceof Component) {
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
		return Helpers::getCsrfToken($this->getSession(), $control, $method, $params);
	}

}
