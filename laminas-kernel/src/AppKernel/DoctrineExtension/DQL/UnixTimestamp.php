<?php

namespace AppKernel\DoctrineExtension\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;

class UnixTimestamp extends FunctionNode {
    /*
     * holds the timestamp of the UNIX_TIMESTAMP DQL statement
     * @var mixed
     */

    protected $dateExpression;

    /**
     * getSql - allows ORM  to inject a UNIX_TIMESTAMP() statement into an SQL string being constructed
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
     * @return void 
     */
    public function getSql(SqlWalker $sqlWalker) {
        return 'UNIX_TIMESTAMP(' .
                $sqlWalker->walkArithmeticExpression($this->dateExpression) .
                ')';
    }

    /**
     * parse - allows DQL to breakdown the DQL string into a processable structure
     * @param \Doctrine\ORM\Query\Parser $parser 
     */
    public function parse(Parser $parser) {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->dateExpression = $parser->ArithmeticExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

}
