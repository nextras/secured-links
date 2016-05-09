<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\SecuredLinks;

use Nette;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Http\Session;


class SecuredRouter implements IRouter
{
	/** signed flag, marks requests which has been signed */
	const SIGNED = 'signed';

	/** length of secret token stored in session */
	const SECURITY_TOKEN_LENGTH = 16;

	/** name of security key which is passed in URL */
	const SECURITY_KEY = '_sec';

	/** @var IRouter */
	private $inner;

	/** @var IPresenterFactory */
	private $presenterFactory;

	/** @var Session */
	private $session;

	/** @var array */
	private $secured;


	/**
	 * @param IRouter           $inner
	 * @param IPresenterFactory $presenterFactory
	 * @param Session           $session
	 * @param array             $secured          describes what should be secured
	 */
	public function __construct(IRouter $inner, IPresenterFactory $presenterFactory, Session $session, array $secured)
	{
		$this->inner = $inner;
		$this->presenterFactory = $presenterFactory;
		$this->session = $session;
		$this->secured = $secured;
	}


	/**
	 * @inheritdoc
	 */
	public function match(Nette\Http\IRequest $httpRequest)
	{
		$appRequest = $this->inner->match($httpRequest);
		if ($appRequest !== NULL && $this->isSignatureOk($appRequest)) {
			$appRequest->setFlag(self::SIGNED);
		}

		return $appRequest;
	}


	/**
	 * @inheritdoc
	 */
	public function constructUrl(Request $appRequest, Nette\Http\Url $refUrl)
	{
		if ($this->isSignatureRequired($appRequest, $ignoredParams)) {
			$params = $appRequest->getParameters();
			$params[self::SECURITY_KEY] = $this->getSignature($appRequest, $ignoredParams);
			$appRequest->setParameters($params);
		}

		return $this->inner->constructUrl($appRequest, $refUrl);
	}


	/**
	 * @param  Request    $appRequest
	 * @return bool
	 */
	private function isSignatureOk(Request $appRequest)
	{
		if ($this->isSignatureRequired($appRequest, $ignoredParams)) {
			$actualSignature = $appRequest->getParameter(self::SECURITY_KEY);
			$expectedSignature = $this->getSignature($appRequest, $ignoredParams);
			return ($actualSignature !== NULL && hash_equals($expectedSignature, $actualSignature));

		} else {
			return TRUE;
		}
	}


	/**
	 * @param  Request    $appRequest
	 * @param  array|bool $ignoredParams
	 * @return bool
	 */
	protected function isSignatureRequired(Request $appRequest, & $ignoredParams)
	{
		$presenterName = $appRequest->getPresenterName();
		$presenterClass = $this->presenterFactory->getPresenterClass($presenterName);

		if (!isset($this->secured[$presenterClass])) {
			return FALSE;
		}

		$params = $appRequest->getParameters();
		foreach ($this->secured[$presenterClass] as $key => $foobar) {
			if (isset($params[$key], $this->secured[$presenterClass][$key][$params[$key]])) {
				$ignoredParams = $this->secured[$presenterClass][$key][$params[$key]];
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * @param  Request    $appRequest
	 * @param  array|bool $ignoredParams
	 * @return string
	 */
	private function getSignature(Request $appRequest, $ignoredParams)
	{
		$sessionSection = $this->session->getSection('Nextras.SecuredLinks');
		if (!isset($sessionSection->token) || strlen($sessionSection->token) !== self::SECURITY_TOKEN_LENGTH) {
			$sessionSection->token = function_exists('random_bytes')
				? random_bytes(self::SECURITY_TOKEN_LENGTH)
				: Nette\Utils\Random::generate(self::SECURITY_TOKEN_LENGTH, "\x00-\xFF");
		}

		if ($ignoredParams === TRUE) {
			$params = $appRequest->getParameters();
		} elseif ($ignoredParams === FALSE) {
			$params = [];
		} else {
			$params = $appRequest->getParameters();
			foreach ($ignoredParams as $key) {
				unset($params[$key]);
			}
		}

		$data = [$this->session->getId(), $appRequest->getPresenterName(), $params];
		$hash = hash_hmac('sha1', json_encode($data), $sessionSection->token);
		return substr($hash, 0, 6);
	}
}
