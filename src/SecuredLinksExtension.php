<?php

/**
 * This file is part of the Nextras Secured Links library.
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\SecuredLinks;

use Generator;
use Nette;
use Nette\Application\IRouter;
use Nette\Application\UI\Presenter;
use Nette\DI\PhpReflection;
use Nette\Neon\Neon;
use Nette\Utils\Strings;
use ReflectionClass;
use ReflectionMethod;


class SecuredLinksExtension extends Nette\DI\CompilerExtension
{
	/** @var array */
	public $defaults = [
		'annotation' => 'secured', // can be NULL to disable
		'destinations' => [],
	];


	/**
	 * @inheritdoc
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('routerFactory'))
			->setImplement(SecuredRouterFactory::class)
			->setParameters(['Nette\Application\IRouter innerRouter'])
			->setArguments([
				$builder->literal('$innerRouter'),
				'@Nette\Application\IPresenterFactory',
				'@Nette\Http\Session',
				$this->findSecuredRequests()
			]);

		$innerRouter = $builder->getByType(IRouter::class);
		$builder->getDefinition($innerRouter)
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('router'))
			->setClass(IRouter::class)
			->setFactory("@{$this->name}.routerFactory::create", ["@$innerRouter"])
			->setAutowired(TRUE);
	}


	/**
	 * @return array
	 */
	private function findSecuredRequests()
	{
		$securedRequests = [];

		foreach ($this->findSecuredDestinations() as $presenterClass => $destinations) {
			foreach ($destinations as $destination => $ignoredParams) {
				if (Strings::endsWith($destination, '!')) {
					$key = 'do';
					$value = Strings::substring($destination, 0, -1);

				} else {
					$key = 'action';
					$value = $destination;
				}

				$securedRequests[$presenterClass][$key][$value] = $ignoredParams;
			}
		}

		return $securedRequests;
	}


	/**
	 * @return Generator
	 */
	private function findSecuredDestinations()
	{
		$config = $this->validateConfig($this->defaults);

		if ($config['annotation']) {
			$presenters = $this->getContainerBuilder()->findByType(Presenter::class);
			foreach ($presenters as $presenterDef) {
				$presenterClass = $presenterDef->getClass();
				$presenterRef = new \ReflectionClass($presenterClass);
				yield $presenterClass => $this->findSecuredMethods($presenterRef);
			}
		}

		foreach ($config['destinations'] as $presenterClass => $destinations) {
			yield $presenterClass => $destinations;
		}
	}


	/**
	 * @param  ReflectionClass $classRef
	 * @return Generator
	 */
	private function findSecuredMethods(ReflectionClass $classRef)
	{
		foreach ($this->findTargetMethods($classRef) as $destination => $methodRef) {
			if ($this->isSecured($methodRef, $ignoredParams)) {
				yield $destination => $ignoredParams;
			}
		}
	}


	/**
	 * @param  ReflectionClass $classRef
	 * @return Generator|ReflectionMethod[]
	 */
	private function findTargetMethods(ReflectionClass $classRef)
	{
		foreach ($classRef->getMethods() as $methodRef) {
			$methodName = $methodRef->getName();

			if (Strings::startsWith($methodName, 'action') && $classRef->isSubclassOf(Presenter::class)) {
				$destination = Strings::firstLower(Strings::after($methodName, 'action'));
				yield $destination => $methodRef;

			} elseif (Strings::startsWith($methodName, 'handle')) {
				$destination = Strings::firstLower(Strings::after($methodName, 'handle')) . '!';
				yield $destination => $methodRef;

			} elseif (Strings::startsWith($methodName, 'createComponent')) {
				$returnType = PhpReflection::getReturnType($methodRef);
				if ($returnType !== NULL) {
					$returnTypeRef = new ReflectionClass($returnType);
					$componentName = Strings::firstLower(Strings::after($methodName, 'createComponent'));
					foreach ($this->findTargetMethods($returnTypeRef) as $innerDestination => $innerRef) {
						yield "$componentName-$innerDestination" => $innerRef;
					}
				}
			}
		}
	}


	/**
	 * @param  ReflectionMethod $ref
	 * @param  array|bool       $params
	 * @return bool
	 */
	private function isSecured(ReflectionMethod $ref, & $params)
	{
		$annotation = preg_quote(isset($this->config['annotation'])
			? $this->config['annotation']
			: $this->defaults['annotation'],
			'#'
		);

		if (preg_match("#^[ \\t/*]*@$annotation(?:[ \\t]+(\\[.*?\\])?|$)#m", $ref->getDocComment(), $matches)) {
			$params = !empty($matches[1]) ? Neon::decode($matches[1]) : TRUE;
			return TRUE;

		} else {
			return FALSE;
		}
	}
}
