<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace NextrasTests\SecuredLinks;

use Mockery;
use Nette;
use Nette\Application\IRouter;
use Nette\Application\LinkGenerator;
use Nette\Application\Routers\Route;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\HttpDI\SessionExtension;
use Nextras\SecuredLinks\SecuredLinksExtension;
use Nextras\SecuredLinks\SecuredRouter;
use Tester;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../fixtures/TestPresenter.php';


class SecuredLinksExtensionTest extends Tester\TestCase
{
	public function testFoo()
	{
		$dic = $this->createContainer();
		$router = $dic->getByType(IRouter::class);
		Assert::type(SecuredRouter::class, $router);

		$linkGenerator = $dic->getByType(LinkGenerator::class);

		Assert::same(
			'http://example.com/test?action=delete&_sec=e646b0',
			$linkGenerator->link('Test:delete')
		);

		Assert::same(
			'http://example.com/test?do=pay&action=default&_sec=eed6d6',
			$linkGenerator->link('Test:default', ['do' => 'pay'])
		);

		Assert::same(
			'http://example.com/test?do=pay&amount=1&action=default&_sec=7eda1c',
			$linkGenerator->link('Test:default', ['do' => 'pay', 'amount' => 1])
		);

		Assert::same(
			'http://example.com/test?do=pay&amount=2&action=default&_sec=f9cc2b',
			$linkGenerator->link('Test:default', ['do' => 'pay', 'amount' => 2])
		);

		Assert::same(
			'http://example.com/test?do=pay2&amount=1&action=default&_sec=51a97a',
			$linkGenerator->link('Test:default', ['do' => 'pay2', 'amount' => 1])
		);

		Assert::same(
			'http://example.com/test?do=pay2&amount=2&action=default&_sec=51a97a', // intentionally the same hash
			$linkGenerator->link('Test:default', ['do' => 'pay2', 'amount' => 2])
		);
	}


	/**
	 * @return \Nette\DI\Container
	 */
	private function createContainer()
	{
		$compiler = new Nette\DI\Compiler;
		$compiler->addExtension('nette.http', new HttpExtension);
		$compiler->addExtension('nette.http.sessions', new SessionExtension);
		$compiler->addExtension('nette.application', new ApplicationExtension(TRUE, [__DIR__ . '/../fixtures']));
		$compiler->addExtension('nextras.securedLinks', new SecuredLinksExtension);

		eval($compiler->compile(
			[
				'services' => [new Nette\DI\Statement(Route::class, ['//example.com/<presenter>'])]
			],
			'SecuredLinksExtensionContainer'
		));

		$sessionSection = Mockery::mock('alias:Nette\Http\SessionSection');
		$sessionSection->token = 'abcdabcdabcdabcd';

		$session = Mockery::mock('Nette\Http\Session');
		$session->shouldReceive('getSection')->with('Nextras.SecuredLinks')->andReturn($sessionSection);
		$session->shouldReceive('getId')->times(8)->andReturn('session_id_1');

		/** @var Nette\DI\Container $dic */
		$dic = new \SecuredLinksExtensionContainer();
		$dic->removeService('nette.http.sessions.session');
		$dic->addService('nette.http.sessions.session', $session);
		return $dic;
	}
}

(new SecuredLinksExtensionTest)->run();
