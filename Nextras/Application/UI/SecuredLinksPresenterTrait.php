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



	public function createSecuredRequest($component, $destination, $args)
	{
		// 1) fragment
		$a = strpos($destination, '#');
		if ($a === FALSE) {
			$fragment = '';
		} else {
			$fragment = substr($destination, $a);
			$destination = substr($destination, 0, $a);
		}

		// 2) ?query syntax
		$a = strpos($destination, '?');
		if ($a !== FALSE) {
			parse_str(substr($destination, $a + 1), $args); // requires disabled magic quotes
			$destination = substr($destination, 0, $a);
		}

		while (TRUE) {
			if ($component instanceof Nette\Application\UI\Presenter) {
				if (substr($destination, -1) !== '!') break;
			} else {
				if ($destination === 'this') break;
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
			if (!$component instanceof Nette\Application\UI\PresenterComponent) break;

			$method = $component->formatSignalMethod($signal);

			$reflection = new PresenterComponentReflection(get_class($component));
			$signalReflection = $reflection->getMethod($method);

			if ($signalReflection->hasAnnotation('secured')) {
				$signalParams = array();
				if ($args) {
					// convert indexed parameters to named
					$_args = $args;
					self::argsToParams(get_class($component), $method, $_args);
					foreach ($signalReflection->getParameters() as $param) {
						if (isset($_args[$param->name])) {
							$signalParams[$param->name] = $_args[$param->name];
						}
					}
				}
				$args['_sec'] = $this->getCsrfToken(get_class($component), $method, $signalParams);
			}

			break;
		}

		return array($destination . $fragment, $args);
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



	/**
	 * Converts list of arguments to named parameters.
	 * @param string class name
	 * @param string method name
	 * @param array arguments
	 * @param array supplemental arguments
	 * @return void
	 * @throws InvalidLinkException
	 */
	private static function argsToParams($class, $method, & $args, $supplemental = array())
	{
		$i = 0;
		$rm = new \ReflectionMethod($class, $method);
		foreach ($rm->getParameters() as $param) {
			$name = $param->getName();
			if (array_key_exists($i, $args)) {
				$args[$name] = $args[$i];
				unset($args[$i]);
				$i++;

			} elseif (array_key_exists($name, $args)) {
				// continue with process

			} elseif (array_key_exists($name, $supplemental)) {
				$args[$name] = $supplemental[$name];

			} else {
				continue;
			}

			if ($args[$name] === NULL) {
				continue;
			}

			$def = $param->isDefaultValueAvailable() && $param->isOptional() ? $param->getDefaultValue() : NULL; // see PHP bug #62988
			$type = $param->isArray() ? 'array' : gettype($def);
			if (!PresenterComponentReflection::convertType($args[$name], $type)) {
				throw new InvalidLinkException("Invalid value for parameter '$name' in method $class::$method(), expected " . ($type === 'NULL' ? 'scalar' : $type) . ".");
			}

			if ($args[$name] === $def || ($def === NULL && is_scalar($args[$name]) && (string) $args[$name] === '')) {
				$args[$name] = NULL; // value transmit is unnecessary
			}
		}

		if (array_key_exists($i, $args)) {
			$method = $rm->getName();
			throw new InvalidLinkException("Passed more parameters than method $class::$method() expects.");
		}
	}

}
