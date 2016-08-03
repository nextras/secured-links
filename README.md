## Nextras SecuredLinks

[![Build Status](https://travis-ci.org/nextras/secured-links.svg?branch=master)](https://travis-ci.org/nextras/secured-links)
[![Downloads this Month](https://img.shields.io/packagist/dm/nextras/secured-links.svg?style=flat)](https://packagist.org/packages/nextras/secured-links)
[![Stable version](http://img.shields.io/packagist/v/nextras/secured-links.svg?style=flat)](https://packagist.org/packages/nextras/secured-links)


**SecuredLinksTrait** creates secured signal links.
**PHP 5.4+ ONLY**

## Installation

The best way to install is using [Composer](http://getcomposer.org/):

```sh
$ composer require nextras/secured-links
```

## Usage of SecuredLinksTrait

```php
<?php
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	use Nextras\Application\UI\SecuredLinksPresenterTrait;
}


class MyPresenter extends BasePresenter
{
	/**
	 * @secured
	 */
	public function handleDelete($id)
	{
	}
}


abstract class BaseControl extends Nette\Application\UI\Control
{
	use Nextras\Application\UI\SecuredLinksControlTrait;
}


class MyControl extends BaseControl
{
	/**
	 * @secured
	 */
	public function handleDelete($id)
	{
	}
}
```
