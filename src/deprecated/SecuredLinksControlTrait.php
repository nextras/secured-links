<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\Application\UI;

use Nette;
use Nette\Application\UI\Presenter;
use Nextras\SecuredLinks\SecuredRouter;


/**
 * @mixin Presenter
 * @deprecated
 */
trait SecuredLinksControlTrait
{
	/**
	 * @deprecated
	 */
	public function signalReceived($signal)
	{
		$methodName = $this->formatSignalMethod($signal);
		if (method_exists($this, $methodName)) {
			$methodRef = new Nette\Reflection\Method($this, $methodName);
			if ($methodRef->hasAnnotation('secured') && !$this->request->hasFlag(SecuredRouter::SIGNED)) {
				$who = $this instanceof Presenter ? 'Presenter' : 'Control';
				throw new \LogicException(
					"$who received request to secured signal which was not properly signed." .
					"This indicate a bug in your installation of Nextras Secured Links." .
					"Please consult documentation on how to properly migrate to Nextras Secured Links 2.0"
				);
			}
		}

		parent::signalReceived($signal);
	}
}
