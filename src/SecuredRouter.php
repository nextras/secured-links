<?php


namespace Nextras\Application\UI;

use Nette;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\Http\Session;
use ReflectionMethod;


class SecuredRouter implements IRouter
{
	/** @var IRouter */
	private $inner;

	/** @var IPresenterFactory */
	private $presenterFactory;

	/** @var Session */
	private $session;


	/**
	 * @param IRouter           $inner
	 * @param IPresenterFactory $presenterFactory
	 * @param Session           $session
	 */
	public function __construct(IRouter $inner, IPresenterFactory $presenterFactory, Session $session)
	{
		$this->inner = $inner;
		$this->presenterFactory = $presenterFactory;
		$this->session = $session;
	}


	/**
	 * @inheritdoc
	 */
	public function match(Nette\Http\IRequest $httpRequest)
	{
		$appRequest = $this->inner->match($httpRequest);

		if ($appRequest !== NULL && $this->isSignatureRequired($appRequest) && !$this->isSignatureValid($appRequest)) {
			return NULL;
		}

		return $appRequest;
	}


	/**
	 * @inheritdoc
	 */
	public function constructUrl(Request $appRequest, Nette\Http\Url $refUrl)
	{
		if ($this->isSignatureRequired($appRequest)) {
			$signature = $this->getSignature($appRequest);
			$appRequest->setParameters(['_sec' => $signature] + $appRequest->getParameters());
		}

		return $this->inner->constructUrl($appRequest, $refUrl);
	}


	/**
	 * @param  Request $appRequest
	 * @return bool
	 */
	protected function isSignatureRequired(Request $appRequest)
	{
		$presenterName = $appRequest->getPresenterName();
		$presenterClass = $this->presenterFactory->getPresenterClass($presenterName);

		if (!is_a($presenterClass, Presenter::class)) {
			return FALSE;
		}

		$params = $appRequest->getParameters();

		if (isset($params['action'])) {
			$methodName = $presenterClass::formatActionMethod($params['action']);
			$methodRef = new ReflectionMethod($presenterClass, $methodName);
			if ($this->isSecured($methodRef)) {
				return TRUE;
			}
		}

		if (isset($params['do'])) {
			$methodName = $presenterClass::formatSignalMethod($params['do']);
			$methodRef = new ReflectionMethod($presenterClass, $methodName);
			if ($this->isSecured($methodRef)) {
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * @param  ReflectionMethod $ref
	 * @return bool
	 */
	public function isSecured(ReflectionMethod $ref)
	{
		return (bool) preg_match('#^[ \t*]*@secured(\s|$)#m', $ref->getDocComment());
	}


	/**
	 * @param  Request $appRequest
	 * @return bool
	 */
	private function isSignatureValid(Request $appRequest)
	{
		$signature = $appRequest->getParameter('_sec');
		return ($signature !== NULL && hash_equals($this->getSignature($appRequest), $signature));
	}


	/**
	 * @param  Request $appRequest
	 * @return string
	 */
	private function getSignature(Request $appRequest)
	{
		$sessionSection = $this->session->getSection('Nextras.Application.UI.SecuredLinksPresenterTrait');
		if (!isset($sessionSection->token)) {
			$sessionSection->token = function_exists('random_bytes')
				? random_bytes(16)
				: Nette\Utils\Random::generate(16, "\x00-\xFF");
		}

		$data = [$this->session->getId(), $appRequest->getPresenterName(), $appRequest->getParameters()];
		$hash = hash_hmac('sha1', serialize($data), $sessionSection->token);
		return substr($hash, 0, 6);
	}
}
