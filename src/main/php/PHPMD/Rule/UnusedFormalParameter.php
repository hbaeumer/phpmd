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
use PHPMD\Node\MethodNode;

/**
 * This rule collects all formal parameters of a given function or method that
 * are not used in a statement of the artifact's body.
 */
class UnusedFormalParameter extends AbstractLocalVariable implements FunctionAware, MethodAware
{
    /**
     * Collected ast nodes.
     *
     * @var \PHPMD\Node\ASTNode[]
     */
    private $nodes = [];

    /**
     * This method checks that all parameters of a given function or method are
     * used at least one time within the artifacts body.
     *
     * @param \PHPMD\AbstractNode $node
     *
     * @return void
     */
    public function apply(AbstractNode $node)
    {
        if ($this->isAbstractMethod($node)) {
            return;
        }

        // Magic methods should be ignored as invalid declarations are picked up by PHP.
        if ($this->isMagicMethod($node)) {
            return;
        }

        if ($this->isInheritedSignature($node)) {
            return;
        }

        if ($this->isNotDeclaration($node)) {
            return;
        }

        $this->nodes = [];

        $this->collectParameters($node);
        $this->removeUsedParameters($node);

        foreach ($this->nodes as $node) {
            $this->addViolation($node, [$node->getImage()]);
        }
    }

    /**
     * Returns <b>true</b> when the given node is an abstract method.
     *
     * @param \PHPMD\AbstractNode $node
     *
     * @return bool
     */
    private function isAbstractMethod(AbstractNode $node)
    {
        if ($node instanceof MethodNode) {
            return $node->isAbstract();
        }

        return false;
    }

    /**
     * Returns <b>true</b> when the given node is method with signature declared as inherited using
     * {@inheritdoc} annotation.
     *
     * @param \PHPMD\AbstractNode $node
     *
     * @return bool
     */
    private function isInheritedSignature(AbstractNode $node)
    {
        if ($node instanceof MethodNode) {
            return preg_match('/\@inheritdoc/i', $node->getDocComment());
        }

        return false;
    }

    /**
     * Returns <b>true</b> when the given node is a magic method signature
     *
     * @param AbstractNode $node
     *
     * @return bool
     */
    private function isMagicMethod(AbstractNode $node)
    {
        static $names = [
            'call',
            'callStatic',
            'get',
            'set',
            'isset',
            'unset',
            'set_state',
        ];

        if ($node instanceof MethodNode) {
            return preg_match('/\__(?:' . implode('|', $names) . ')/i', $node->getName());
        }

        return false;
    }

    /**
     * Tests if the given <b>$node</b> is a method and if this method is also
     * the initial declaration.
     *
     * @param \PHPMD\AbstractNode $node
     *
     * @return bool
     *
     * @since 1.2.1
     */
    private function isNotDeclaration(AbstractNode $node)
    {
        if ($node instanceof MethodNode) {
            return ! $node->isDeclaration();
        }

        return false;
    }

    /**
     * This method extracts all parameters for the given function or method node
     * and it stores the parameter images in the <b>$_images</b> property.
     *
     * @param \PHPMD\AbstractNode $node
     *
     * @return void
     */
    private function collectParameters(AbstractNode $node)
    {
        // First collect the formal parameters container
        $parameters = $node->getFirstChildOfType('FormalParameters');

        // Now get all declarators in the formal parameters container
        $declarators = $parameters->findChildrenOfType('VariableDeclarator');

        foreach ($declarators as $declarator) {
            $this->nodes[$declarator->getImage()] = $declarator;
        }
    }

    /**
     * This method collects all local variables in the body of the currently
     * analyzed method or function and removes those parameters that are
     * referenced by one of the collected variables.
     *
     * @param \PHPMD\AbstractNode $node
     *
     * @return void
     */
    private function removeUsedParameters(AbstractNode $node)
    {
        $variables = $node->findChildrenOfType('Variable');
        foreach ($variables as $variable) {
            /** @var $variable ASTNode */
            if (! $this->isRegularVariable($variable)) {
                continue;
            }

            unset($this->nodes[$variable->getImage()]);
        }

        $compoundVariables = $node->findChildrenOfType('CompoundVariable');
        foreach ($compoundVariables as $compoundVariable) {
            $variablePrefix = $compoundVariable->getImage();

            foreach ($compoundVariable->findChildrenOfType('Expression') as $child) {
                $variableImage = $variablePrefix . $child->getImage();

                if (! isset($this->nodes[$variableImage])) {
                    continue;
                }

                unset($this->nodes[$variableImage]);
            }
        }

        /* If the method calls func_get_args() then all parameters are
         * automatically referenced */
        $functionCalls = $node->findChildrenOfType('FunctionPostfix');
        foreach ($functionCalls as $functionCall) {
            if ($this->isFunctionNameEqual($functionCall, 'func_get_args')) {
                $this->nodes = [];
            }

            if (! $this->isFunctionNameEndingWith($functionCall, 'compact')) {
                continue;
            }

            foreach ($functionCall->findChildrenOfType('Literal') as $literal) {
                unset($this->nodes['$' . trim($literal->getImage(), '"\'')]);
            }
        }
    }
}
