<?php declare(strict_types=1);

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\Application\UI;

use Nette;


trait SecuredLinksControlTrait
{

	/**
	 * {@inheritdoc}
	 */
	public function link(string $destination, ...$args): string
	{
		$args = count($args) === 1 && is_array($args[0] ?? null)
			? $args[0]
			: $args;

		$link = parent::link($destination, $args);
		return $this->getPresenter()->createSecuredLink($this, $link, $destination);
	}


	/**
	 * For @secured annotated signal handler methods checks if URL parameters has not been changed
	 *
	 * @throws Nette\Application\UI\BadSignalException if there is no handler method or the security token does not match
	 * @throws \LogicException if there is no redirect in a secured signal
	 */
	public function signalReceived(string $signal): void
	{
		$method = $this->formatSignalMethod($signal);
		$secured = FALSE;

		if (method_exists($this, $method)) {
			$reflection = new \ReflectionMethod($this, $method);
			$secured = Nette\Application\UI\ComponentReflection::parseAnnotation($reflection, 'secured') !== NULL;
			if ($secured) {
				$params = array($this->getUniqueId());
				if ($this->params) {
					foreach ($reflection->getParameters() as $param) {
						if ($param->isOptional()) {
							continue;
						}
						if (isset($this->params[$param->name])) {
							$params[$param->name] = $this->params[$param->name];
							$type = Nette\Application\UI\ComponentReflection::getParameterType($param);
							Nette\Application\UI\ComponentReflection::convertType($params[$param->name], $type);
						}
					}
				}

				if (!isset($this->params['_sec']) || $this->params['_sec'] !== $this->getPresenter()->getCsrfToken(get_class($this), $method, $params)) {
					throw new Nette\Application\UI\BadSignalException("Invalid security token for signal '$signal' in class {$this->getReflection()->name}.");
				}
			}
		}

		parent::signalReceived($signal);

		if ($secured && !$this->getPresenter()->isAjax()) {
			throw new \LogicException("Secured signal '$signal' did not redirect. Possible csrf-token reveal by http referer header.");
		}
	}

}
