<?php
/**
 * This file is part of PHP Mess Detector.
 *
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 *
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @link http://phpmd.org/
 *
 * @author Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license https://opensource.org/licenses/bsd-license.php BSD License
 */

namespace PHPMD\Rule;

use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Node\ASTNode;

/**
 * Base class for rules that rely on local variables.
 *
 * @since     0.2.6
 */
abstract class AbstractLocalVariable extends AbstractRule
{
    /**
     * PHP super globals that are available in all php scopes, so that they
     * can never be unused local variables.
     *
     * @link http://php.net/manual/en/reserved.variables.php
     *
     * @var array<string, bool>
     */
    private static $superGlobals = [
        '$argc' => true,
        '$argv' => true,
        '$_COOKIE' => true,
        '$_ENV' => true,
        '$_FILES' => true,
        '$_GET' => true,
        '$_POST' => true,
        '$_REQUEST' => true,
        '$_SERVER' => true,
        '$_SESSION' => true,
        '$GLOBALS' => true,
        '$HTTP_RAW_POST_DATA' => true,
        '$php_errormsg' => true,
        '$http_response_header' => true,
    ];

    /**
     * Tests if the given variable node represents a local variable or if it is
     * a static object property or something similar.
     *
     * @param \PHPMD\Node\ASTNode $variable The variable to check.
     *
     * @return bool
     */
    protected function isLocal(ASTNode $variable)
    {
        return $variable->isThis() === false
            && $this->isNotSuperGlobal($variable)
            && $this->isRegularVariable($variable);
    }

    /**
     * Tests if the given variable represents one of the PHP super globals
     * that are available in scopes.
     *
     * @param \PHPMD\AbstractNode $variable
     *
     * @return bool
     */
    protected function isNotSuperGlobal(AbstractNode $variable)
    {
        return ! isset(self::$superGlobals[$variable->getImage()]);
    }

    /**
     * Tests if the given variable node is a regular variable an not property
     * or method postfix.
     *
     * @param \PHPMD\Node\ASTNode $variable
     *
     * @return bool
     */
    protected function isRegularVariable(ASTNode $variable)
    {
        $node   = $this->stripWrappedIndexExpression($variable);
        $parent = $node->getParent();

        if ($parent->isInstanceOf('PropertyPostfix')) {
            $primaryPrefix = $parent->getParent();
            if ($primaryPrefix->getParent()->isInstanceOf('MemberPrimaryPrefix')) {
                return ! $primaryPrefix->getParent()->isStatic();
            }

            return $parent->getChild(0)->getNode() !== $node->getNode()
                || ! $primaryPrefix->isStatic();
        }

        return true;
    }

    /**
     * Removes all index expressions that are wrapped around the given node
     * instance.
     *
     * @param \PHPMD\Node\ASTNode $node
     *
     * @return \PHPMD\Node\ASTNode
     */
    protected function stripWrappedIndexExpression(ASTNode $node)
    {
        if ($this->isWrappedByIndexExpression($node) === false) {
            return $node;
        }

        $parent = $node->getParent();
        if ($parent->getChild(0)->getNode() === $node->getNode()) {
            return $this->stripWrappedIndexExpression($parent);
        }

        return $node;
    }

    /**
     * Tests if the given variable node os part of an index expression.
     *
     * @param \PHPMD\Node\ASTNode $node
     *
     * @return bool
     */
    protected function isWrappedByIndexExpression(ASTNode $node)
    {
        return $node->getParent()->isInstanceOf('ArrayIndexExpression')
            || $node->getParent()->isInstanceOf('StringIndexExpression');
    }

    /**
     * PHP is case insensitive so we should compare function names case
     * insensitive.
     *
     * @param \PHPMD\AbstractNode $node
     * @param string              $name
     *
     * @return bool
     */
    protected function isFunctionNameEqual(AbstractNode $node, $name)
    {
        return strcasecmp(trim($node->getImage(), '\\'), $name) === 0;
    }

    /**
     * AST puts namespace prefix to global functions called from a namespace.
     * This method checks if the last part of function fully qualified name is equal to $name
     *
     * @param \PHPMD\AbstractNode $node
     * @param string              $name
     *
     * @return bool
     */
    protected function isFunctionNameEndingWith(AbstractNode $node, $name)
    {
        $parts = explode('\\', trim($node->getImage(), '\\'));

        return strcasecmp(array_pop($parts), $name) === 0;
    }
}
