<?php
/**
 * @phpVersion >= 7.0.0
 */

use Nette\Application\Request;
use Nette\Application\Routers\SimpleRouter;
use Nette\Application\UI\BadSignalException;
use Nette\Application\UI\Presenter;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Nextras\Application\UI\SecuredLinksPresenterTrait;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestPresenter extends Presenter
{
	use SecuredLinksPresenterTrait;


	public function renderDefault()
	{
		$this->terminate();
	}


	/** @secured */
	public function handlePay(bool $value)
	{
		$this->redirect('this');
	}

}


$url = new UrlScript('http://localhost/index.php', '/index.php');

$httpRequest = new HttpRequest($url);
$httpResponse = new Response();

$router = new SimpleRouter();
$request = new Request('Test', HttpRequest::GET, []);

$sessionSection = Mockery::mock('alias:Nette\Http\SessionSection');
$sessionSection->token = 'abcd';

$session = Mockery::mock('Nette\Http\Session');
$session->shouldReceive('getSection')->with('Nextras.Application.UI.SecuredLinksPresenterTrait')->andReturn($sessionSection);
$session->shouldReceive('getId')->andReturn('session_id_1');

$presenter = new TestPresenter();
$presenter->autoCanonicalize = FALSE;
$presenter->injectPrimary(NULL, NULL, $router, $httpRequest, $httpResponse, $session, NULL);
$presenter->run($request);

Assert::same('/index.php?value=0&action=default&do=pay&presenter=Test&_sec=JqCasYHU', $presenter->link('pay!', [FALSE]));

$presenter->run(new Request('Test', 'GET', [
	'action' => 'default',
	'do' => 'pay',
	'value' => '0',
	'_sec' => 'JqCasYHU',
]));

Assert::exception(function () use ($presenter) {
	$presenter->run(new Request('Test', 'GET', [
		'action' => 'default',
		'do' => 'pay',
		'value' => '0',
		//'_sec' => 'JqCasYHU',
	]));
}, BadSignalException::class, "Invalid security token for signal 'pay' in class TestPresenter.");

Mockery::close();
