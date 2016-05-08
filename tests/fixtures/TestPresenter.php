<?php

use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;


class TestControl extends Control
{
	/** @secured */
	public function handlePay($amount = 0)
	{
	}
}


class TestPresenter extends Presenter
{
	public function renderDefault()
	{
		$this->terminate();
	}


	/** @secured */
	public function handlePay($amount)
	{
	}


	/** @secured [amount] */
	public function handlePay2($amount)
	{
	}


	/** @secured */
	public function handleList(array $sections)
	{
	}


	/**
	 * @secured
	 */
	public function actionDelete()
	{

	}


	/**
	 * @return TestControl
	 */
	protected function createComponentMyControl()
	{
		return new TestControl();
	}
}
