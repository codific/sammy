<?php
/**
 * This is automatically generated file using the Codific Prototizer.
 *
 * PHP version 8
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.eu>
 *
 * @see     http://codific.eu
 */

declare(strict_types=1);

namespace App\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

class CastForLike extends FunctionNode
{
    public $stringPrimary;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'LOWER(CAST('.$sqlWalker->walkStringPrimary($this->stringPrimary).' AS text))';
    }

    /**
     * @throws QueryException
     * @deprecated
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->stringPrimary = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
