<?php

use Nette\Application\Request;
use Nette\Application\Routers\SimpleRouter;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nextras\Application\UI\SecuredLinksControlTrait;
use Nextras\Application\UI\SecuredLinksPresenterTrait;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestControl extends Control
{
	use SecuredLinksControlTrait;
	/** @secured */
	public function handlePay($amount = 0)
	{
	}
}


class TestPresenter extends Presenter
{
	use SecuredLinksPresenterTrait;
	protected function startup()
	{
		parent::startup();
		$this['mycontrol'] = new TestControl;
	}
	public function renderDefault()
	{
		$this->terminate();
	}
	/** @secured */
	public function handlePay($amount = 0)
	{
	}
	/** @secured */
	public function handlePay2($amount)
	{
	}
	/** @secured */
	public function handleList(array $sections)
	{
	}
}


$url = new UrlScript('http://localhost/index.php');
$url->setScriptPath('/index.php');

$httpRequest = new HttpRequest($url);
$httpResponse = new Response();

$router = new SimpleRouter();
$request = new Request('Test', HttpRequest::GET, array());

$session = new Session($httpRequest, $httpResponse);
$section = $session->getSection('Nextras.Application.UI.SecuredLinksPresenterTrait');
$section->token = 'abcd';

$presenter = new TestPresenter();
$presenter->autoCanonicalize = FALSE;
$presenter->injectPrimary(NULL, NULL, $router, $httpRequest, $httpResponse, $session, NULL);
$presenter->run($request);


Assert::same( '/index.php?action=default&do=pay&presenter=Test&_sec=8607a814', $presenter->link('pay!') );
Assert::same( '/index.php?amount=200&action=default&do=pay&presenter=Test&_sec=8607a814', $presenter->link('pay!', [200]) );
Assert::same( '/index.php?amount=100&action=default&do=pay2&presenter=Test&_sec=948fe21d', $presenter->link('pay2!', [100]) );
Assert::same( '/index.php?amount=200&action=default&do=pay2&presenter=Test&_sec=03c6b49d', $presenter->link('pay2!', [200]) );
Assert::same( '/index.php?sections[0]=a&sections[1]=b&action=default&do=list&presenter=Test&_sec=9d4a84be', urldecode($presenter->link('list!', [['a', 'b']])) );
Assert::same( '/index.php?sections[0]=a&sections[1]=c&action=default&do=list&presenter=Test&_sec=fe7a715e', urldecode($presenter->link('list!', [['a', 'c']])) );

Assert::same( '/index.php?action=default&do=mycontrol-pay&presenter=Test&mycontrol-_sec=573011fa', $presenter['mycontrol']->link('pay') );
Assert::same( '/index.php?mycontrol-amount=200&action=default&do=mycontrol-pay&presenter=Test&mycontrol-_sec=573011fa', $presenter['mycontrol']->link('pay', [200]) );
