<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use jeremykenedy\laravelusers\App\Http\Middleware\VerifyImpersonationState;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;

/**
 * Visits route syntax nodes and constructs the matching guard expression using parser node types.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class RoutingMiddlewareVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node): ?Node
    {
        if (!$this->isRouteCall($node) || !$node->name instanceof Node\Identifier || !($node->args[0] ?? null) instanceof Node\Arg) {
            return null;
        }
        if ($node->name->toString() === 'middleware') {
            $node->args[0]->value = $this->withGuard($node->args[0]->value);
        } elseif ($node->name->toString() === 'group') {
            $this->visitGroup($node->args[0]->value);
        }

        return $node;
    }

    private function visitGroup(Expr $value): void
    {
        if (!$value instanceof Expr\Array_) {
            return;
        }
        foreach ($value->items as $item) {
            if ($item?->key instanceof String_ && $item->key->value === 'middleware') {
                $item->value = $this->withGuard($item->value);
            }
        }
    }

    private function isRouteCall(Node $node): bool
    {
        if ($node instanceof Expr\MethodCall) {
            return $this->isRouteCall($node->var);
        }
        if (!$node instanceof Expr\StaticCall || !$node->class instanceof Name) {
            return false;
        }
        $class = $node->class->getAttribute('resolvedName', $node->class)->toString();

        return in_array($class, ['Route', 'Illuminate\\Support\\Facades\\Route'], true);
    }

    private function withGuard(Expr $value): Expr
    {
        if (!$value instanceof Expr\Array_ && !$value instanceof String_) {
            return $value;
        }
        $items = $value instanceof Expr\Array_ ? $value->items : [new Expr\ArrayItem($value)];
        foreach ($items as $item) {
            $entry = $item?->value;
            if ($entry && $this->containsGuard($entry)) {
                return $value;
            }
        }
        $class = new Expr\ClassConstFetch(new Name\FullyQualified(VerifyImpersonationState::class), 'class');
        $guard = new Expr\Ternary(
            new Expr\FuncCall(new Name\FullyQualified('class_exists'), [new Node\Arg(clone $class)]),
            new Expr\Array_([new Expr\ArrayItem($class)], ['kind' => Expr\Array_::KIND_SHORT]),
            new Expr\Array_([], ['kind' => Expr\Array_::KIND_SHORT])
        );
        $items[] = new Expr\ArrayItem($guard, null, false, [], true);
        if ($value instanceof Expr\Array_) {
            $value->items = $items;

            return $value;
        }

        return new Expr\Array_($items, ['kind' => Expr\Array_::KIND_SHORT]);
    }

    private function containsGuard(Expr $value): bool
    {
        if ($value instanceof String_ && ltrim($value->value, '\\') === VerifyImpersonationState::class) {
            return true;
        }

        return (new NodeFinder())->findFirst($value, fn ($node) => $this->isGuardReference($node)) !== null;
    }

    private function isGuardReference(Node $node): bool
    {
        return $node instanceof Expr\ClassConstFetch && $node->class instanceof Name
            && $node->class->getAttribute('resolvedName', $node->class)->toString() === VerifyImpersonationState::class;
    }
}
