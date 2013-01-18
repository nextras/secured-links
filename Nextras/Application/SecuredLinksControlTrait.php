<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 * @author     Jan Skrasek
 */

namespace Nextras\Application;

use Nette;



trait SecuredLinksControlTrait
{

	/**
	 * {@inheritdoc}
	 */
	public function link($destination, $args = array())
	{
		if (!is_array($args)) {
			$args = func_get_args();
			array_shift($args);
		}

		list($destination, $args) = $this->getPresenter()->createSecuredRequest($this, $destination, $args);
		return parent::link($destination, $args);
	}



	/**
	 * For @secured annotated signal handler methods checks if URL parameters has not been changed
	 *
	 * @throws Nette\Application\UI\BadSignalException if there is not handler method or security token does not match
	 * @throws \RuntimeException if there is no redirect in a secured signal
	 */
	public function signalReceived($signal)
	{
		$method = $this->formatSignalMethod($signal);
		$reflection = new Nette\Reflection\Method($this, $method);
		if ($reflection->hasAnnotation('secured')) {
			$params = array();
			if ($this->params) {
				foreach ($reflection->getParameters() as $param) {
					if (isset($this->params[$param->name])) {
						$params[$param->name] = $this->params[$param->name];
					}
				}
			}
			if (!isset($this->params['_sec']) || $this->params['_sec'] !== $this->getPresenter()->getCsrfToken(get_class($this), $method, $params)) {
				throw new Nette\Application\UI\BadSignalException("Invalid security token for signal '$signal' in class {$this->reflection->name}.");
			}
		}

		parent::signalReceived($signal);

		if (isset($this->params['_sec'])) {
			throw new \RuntimeException("Secured signal '$signal' did not redirect. Possible csrf-token reveal by http referer header.");
		}
	}

}
