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

$sessionSection = Mockery::mock('alias:Nette\Http\SessionSection');
$sessionSection->token = 'abcd';

$session = Mockery::mock('Nette\Http\Session');
$session->shouldReceive('getSection')->with('Nextras.Application.UI.SecuredLinksPresenterTrait')->andReturn($sessionSection);
$session->shouldReceive('getId')->times(8)->andReturn('session_id_1');

$presenter = new TestPresenter();
$presenter->autoCanonicalize = FALSE;
$presenter->injectPrimary(NULL, NULL, $router, $httpRequest, $httpResponse, $session, NULL);
$presenter->run($request);


Assert::same( '/index.php?action=default&do=pay&presenter=Test&_sec=15b97390', $presenter->link('pay!') );
Assert::same( '/index.php?amount=200&action=default&do=pay&presenter=Test&_sec=15b97390', $presenter->link('pay!', [200]) );
Assert::same( '/index.php?amount=100&action=default&do=pay2&presenter=Test&_sec=1292dd35', $presenter->link('pay2!', [100]) );
Assert::same( '/index.php?amount=200&action=default&do=pay2&presenter=Test&_sec=6c9cc123', $presenter->link('pay2!', [200]) );
Assert::same( '/index.php?sections[0]=a&sections[1]=b&action=default&do=list&presenter=Test&_sec=52c37d1f', urldecode($presenter->link('list!', [['a', 'b']])) );
Assert::same( '/index.php?sections[0]=a&sections[1]=c&action=default&do=list&presenter=Test&_sec=a0f08fca', urldecode($presenter->link('list!', [['a', 'c']])) );

Assert::same( '/index.php?action=default&do=mycontrol-pay&presenter=Test&mycontrol-_sec=3370fd04', $presenter['mycontrol']->link('pay') );
Assert::same( '/index.php?mycontrol-amount=200&action=default&do=mycontrol-pay&presenter=Test&mycontrol-_sec=3370fd04', $presenter['mycontrol']->link('pay', [200]) );


$session->shouldReceive('getId')->times(2)->andReturn('session_id_2');

Assert::same( '/index.php?sections[0]=a&sections[1]=b&action=default&do=list&presenter=Test&_sec=ea0c9a62', urldecode($presenter->link('list!', [['a', 'b']])) );
Assert::same( '/index.php?sections[0]=a&sections[1]=c&action=default&do=list&presenter=Test&_sec=a4ddd9f8', urldecode($presenter->link('list!', [['a', 'c']])) );

Mockery::close();
