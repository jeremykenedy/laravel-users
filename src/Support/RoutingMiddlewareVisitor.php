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

class RoutingMiddlewareVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node): ?Node
    {
        if (!$this->isRouteCall($node) || !$node->name instanceof Node\Identifier || empty($node->args[0])) {
            return null;
        }
        if ($node->name->toString() === 'middleware') {
            $node->args[0]->value = $this->withGuard($node->args[0]->value);
        } elseif ($node->name->toString() === 'group' && $node->args[0]->value instanceof Expr\Array_) {
            foreach ($node->args[0]->value->items as $item) {
                if ($item?->key instanceof String_ && $item->key->value === 'middleware') {
                    $item->value = $this->withGuard($item->value);
                }
            }
        }

        return $node;
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
            $reference = $entry ? (new NodeFinder())->findFirst($entry, fn ($node) => $node instanceof Expr\ClassConstFetch && $node->class instanceof Name && $node->class->getAttribute('resolvedName', $node->class)->toString() === VerifyImpersonationState::class) : null;
            if ($reference) {
                return $value;
            }
            if ($entry instanceof String_ && ltrim($entry->value, '\\') === VerifyImpersonationState::class) {
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
}
