<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\App\Http\Middleware\VerifyImpersonationState;
use PhpParser\Error;
use PhpParser\Lexer\Emulative;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;

class HostRouting
{
    public function __construct(private Filesystem $files, private ParserFactory $factory)
    {
    }

    public function prepare(): ?array
    {
        $path = base_path('routes/web.php');
        if (!$this->files->exists($path)) {
            return null;
        }
        if (is_link($path)) {
            throw new RuntimeException('The web routes file cannot be a symbolic link.');
        }
        $source = $this->files->get($path);
        $lexer = null;
        if (method_exists($this->factory, 'createForNewestSupportedVersion')) {
            $parser = $this->factory->createForNewestSupportedVersion();
        } else {
            $lexer = new Emulative(['usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos']]);
            $parser = $this->factory->create(ParserFactory::PREFER_PHP7, $lexer);
        }

        try {
            $original = $parser->parse($source) ?? [];
            $tokens = method_exists($parser, 'getTokens') ? $parser->getTokens() : $lexer->getTokens();
            $cloner = new NodeTraverser();
            $cloner->addVisitor(new CloningVisitor());
            $statements = $cloner->traverse($original);
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver(null, ['replaceNodes' => false]));
            $traverser->addVisitor(new RoutingMiddlewareVisitor());
            $statements = $traverser->traverse($statements);
            $statements = $this->registerGuard($statements);
            $updated = (new Standard())->printFormatPreserving($statements, $original, $tokens);
            $parser->parse($updated);
        } catch (Error $exception) {
            throw new RuntimeException('Unable to parse routes/web.php. No route changes were written.', previous: $exception);
        }

        return ['path' => $path, 'original' => $source, 'updated' => $updated];
    }

    public function write(?array $plan): void
    {
        if (!$plan || $plan['updated'] === $plan['original']) {
            return;
        }
        if ($this->files->get($plan['path']) !== $plan['original']) {
            throw new RuntimeException('The web routes file changed during setup. Run the command again.');
        }
        $this->files->replace($plan['path'], $plan['updated'], fileperms($plan['path']) & 0777);
    }

    private function registerGuard(array $statements): array
    {
        $registered = (new NodeFinder())->findFirst($statements, function ($node) {
            return $node instanceof Expr\StaticCall && $node->name instanceof Identifier
                && $node->name->toString() === 'pushMiddlewareToGroup'
                && ($node->args[0]->value ?? null) instanceof String_ && $node->args[0]->value->value === 'web'
                && ($node->args[1]->value ?? null) instanceof Expr\ClassConstFetch
                && $node->args[1]->value->class instanceof Name
                && $node->args[1]->value->class->getAttribute('resolvedName', $node->args[1]->value->class)->toString() === VerifyImpersonationState::class;
        });
        if ($registered) {
            return $statements;
        }
        foreach ($statements as $statement) {
            if ($statement instanceof Stmt\Namespace_) {
                $statement->stmts = $this->insertRegistration($statement->stmts);

                return $statements;
            }
        }

        return $this->insertRegistration($statements);
    }

    private function insertRegistration(array $statements): array
    {
        $index = 0;
        foreach ($statements as $statement) {
            if (!$statement instanceof Stmt\Declare_ && !$statement instanceof Stmt\Use_ && !$statement instanceof Stmt\GroupUse) {
                break;
            }
            $index++;
        }
        $registration = new Stmt\Expression(new Expr\StaticCall(new Name\FullyQualified('Illuminate\\Support\\Facades\\Route'), 'pushMiddlewareToGroup', [
            new Arg(new String_('web')),
            new Arg(new Expr\ClassConstFetch(new Name\FullyQualified(VerifyImpersonationState::class), 'class')),
        ]));
        $conditional = new Stmt\If_(new Expr\FuncCall(new Name\FullyQualified('class_exists'), [
            new Arg(new Expr\ClassConstFetch(new Name\FullyQualified(VerifyImpersonationState::class), 'class')),
        ]), ['stmts' => [$registration]]);
        array_splice($statements, $index, 0, [$conditional]);

        return $statements;
    }
}
