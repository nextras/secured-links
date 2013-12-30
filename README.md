## Nextras\SecuredLinks

**SecuredLinksTrait** creates secured signal links.
**PHP 5.4+ ONLY**

## Installation

The best way to install is using [Composer](http://getcomposer.org/):

```sh
$ composer require nextras/secured-links
```

## Usage of SecuredLinksTrait

```php
abstract class BasePrenseter extends Nette\Application\UI\Presenter
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
