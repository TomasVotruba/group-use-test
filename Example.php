<?php

use PhpParser\Lexer;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

require __DIR__ . '/vendor/autoload.php';

$parserFactory = new ParserFactory();
$lexer = new Lexer\Emulative([
    'usedAttributes' => [
        'comments',
        'startLine', 'endLine',
        'startTokenPos', 'endTokenPos',
    ],
]);
$parser = $parserFactory->create(ParserFactory::PREFER_PHP7, $lexer);

$stmts = $parser->parse(file_get_contents(__DIR__ . '/some_file.php'));
$origStmts = $stmts;

$traverser = new \PhpParser\NodeTraverser();
$traverser->addVisitor(new \PhpParser\NodeVisitor\CloningVisitor());
$traverser->traverse($stmts);

$traverser = new \PhpParser\NodeTraverser();
$traverser->addVisitor(new class extends \PhpParser\NodeVisitorAbstract {
    public function leaveNode(\PhpParser\Node $node)
    {
        if (! $node instanceof GroupUse) {
            return $node;
        }

        $prefix = $node->prefix->toString();

        $uses = [];
        foreach ($node->uses as $useUse) {
            $name = new Name($prefix . '\\' . $useUse->name->toString());
            $useUse = new UseUse($name);

            $uses[] = new Use_([$useUse], $node->type);
        }

        return $uses;
    }

});


$traverser->traverse($stmts);

$standard = new Standard();
dump($standard->printFormatPreserving($stmts, $origStmts, $lexer->getTokens()));
