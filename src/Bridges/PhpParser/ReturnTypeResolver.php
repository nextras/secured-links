<?php

/**
 * This file is part of the Nextras Secured Links library.
 *
 * @license    MIT
 * @link       https://github.com/nextras/secured-links
 */

namespace Nextras\SecuredLinks\Bridges\PhpParser;

use Nette;
use Nette\DI\PhpReflection;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use ReflectionMethod;


class ReturnTypeResolver extends NodeVisitorAbstract
{
	/** @var string */
	private $className;

	/** @var string */
	private $methodName;

	/** @var string[] */
	private $returnTypes = [];

	/** @var string[][] */
	private $varTypes = [];

	/** @var bool */
	private $inClass = FALSE;

	/** @var bool */
	private $inMethod = FALSE;


	/**
	 * @param string $className
	 * @param string $methodName
	 */
	public function __construct($className, $methodName)
	{
		$this->className = $className;
		$this->methodName = $methodName;
		$this->varTypes['this'][] = $className;
	}


	/**
	 * @param  ReflectionMethod $methodRef
	 * @return NULL|string
	 */
	public static function getReturnType(ReflectionMethod $methodRef)
	{
		$fileContent = file_get_contents($methodRef->getDeclaringClass()->getFileName());

		$traverser = new NodeTraverser();
		$traverser->addVisitor(new NameResolver);
		$traverser->addVisitor($resolver = new self($methodRef->getDeclaringClass()->getName(), $methodRef->getName()));
		$traverser->traverse((new ParserFactory)->create(ParserFactory::PREFER_PHP7)->parse($fileContent));

		return count($resolver->returnTypes) === 1 ? $resolver->returnTypes[0] : NULL;
	}


	/**
	 * @inheritdoc
	 */
	public function enterNode(Node $node)
	{
		if ($node instanceof Node\Stmt\Class_ && $node->name === $this->className) {
			$this->inClass = TRUE;

		} elseif ($this->inClass && $node instanceof Node\Stmt\ClassMethod && $node->name === $this->methodName) {
			$this->inMethod = TRUE;

		} elseif ($this->inMethod) {
			if ($node instanceof Node\Stmt\Return_ && $node->expr !== NULL) {
				foreach ($this->getExpressionTypes($node->expr) as $type) {
					$this->addReturnType($type);
				}

			} elseif ($node instanceof Node\Expr\Assign) {
				foreach ($this->getExpressionTypes($node->expr) as $type) {
					$this->addVarType($node, $type);
				}
			}
		}
	}


	/**
	 * @inheritdoc
	 */
	public function leaveNode(Node $node)
	{
		if ($this->inMethod && $node instanceof Node\Stmt\ClassMethod) {
			$this->inMethod = FALSE;

		} elseif ($this->inClass && $node instanceof Node\Stmt\Class_) {
			$this->inClass = FALSE;
		}
	}


	/**
	 * @param  Node\Expr $expr
	 * @return string[]
	 */
	private function getExpressionTypes(Node\Expr $expr)
	{
		$result = [];

		if ($expr instanceof Node\Expr\New_) {
			if ($expr->class instanceof Node\Name) {
				$result[] = (string) $expr->class;
			}

		} elseif ($expr instanceof Node\Expr\Variable) {
			if (is_string($expr->name) && isset($this->varTypes[$expr->name])) {
				$result = $this->varTypes[$expr->name];
			}

		} elseif ($expr instanceof Node\Expr\PropertyFetch) {
			if (is_string($expr->name)) {
				foreach ($this->getExpressionTypes($expr->var) as $objType) {
					$propertyRef = new \ReflectionProperty($objType, $expr->name);
					$type = PhpReflection::parseAnnotation($propertyRef, 'var');
					$type = $type ? PhpReflection::expandClassName($type, PhpReflection::getDeclaringClass($propertyRef)) : NULL;
					$result[] = $type;
				}
			}

		} elseif ($expr instanceof Node\Expr\MethodCall) {
			if (is_string($expr->name)) {
				foreach ($this->getExpressionTypes($expr->var) as $objType) {
					$methodRef = new \ReflectionMethod($objType, $expr->name);
					$result[] = PhpReflection::getReturnType($methodRef);
				}
			}

		} elseif ($expr instanceof Node\Expr\Assign) {
			foreach ($this->getExpressionTypes($expr->expr) as $type) {
				$this->addVarType($expr, $type);
				$result[] = $type;
			}

		} elseif ($expr instanceof Node\Expr\Clone_) {
			$result = $this->getExpressionTypes($expr->expr);
		}

		return $result;
	}


	/**
	 * @param  string $exprType
	 * @return void
	 */
	private function addReturnType($exprType)
	{
		if ($exprType !== NULL && class_exists($exprType) && !in_array($exprType, $this->returnTypes)) {
			$this->returnTypes[] = $exprType;
		}
	}


	/**
	 * @param  Node\Expr\Assign $node
	 * @param  string           $exprType
	 * @return void
	 */
	private function addVarType($node, $exprType)
	{
		if ($node->var instanceof Node\Expr\Variable && is_string($node->var->name)
			&& (empty($this->varTypes[$node->var->name]) || !in_array($exprType, $this->varTypes[$node->var->name]))
			&& $exprType !== NULL && class_exists($exprType)
		) {
			$this->varTypes[$node->var->name][] = $exprType;
		}
	}
}
