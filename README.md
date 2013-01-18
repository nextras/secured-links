## Nextras\Application
List of components:
- **SecuredLinksTrait**: creates secured signal links

## Installation

The best way to install is using [Composer](http://getcomposer.org/):

```sh
$ composer require nextras/application
```

## Usage of SecuredLinksTrait

```php
class BasePrenseter extends Nette\Application\UI\Presenter
{
	use Nextras\Application\SecuredLinksPresenterTrait;
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


class BaseControl extends Nette\Application\UI\Control
{
	use Nextras\Application\SecuredLinksControlTrait;
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
