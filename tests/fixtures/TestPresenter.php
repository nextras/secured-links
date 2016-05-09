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

interface TestControlFactory
{

	/**
	 * @return TestControl
	 */
	public function create();
}


class TestPresenter extends Presenter
{
	/** @var TestControl */
	public $testControl;

	/** @var TestControlFactory */
	public $testControlFactory;


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
	protected function createComponentMyControlA()
	{

	}


	protected function createComponentMyControlB()
	{
		return new TestControl();
	}


	protected function createComponentMyControlC()
	{
		$tmp = new TestControl();
		$control = $tmp;
		return $control;
	}


	protected function createComponentMyControlD()
	{
		return clone $this->testControl;
	}


	protected function createComponentMyControlE()
	{
		return $this->testControlFactory->create();
	}
}
