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

namespace PHPMD\Node;

use PDepend\Source\AST\ASTInterface;

/**
 * Wrapper around PHP_Depend's interface objects.
 */
class InterfaceNode extends AbstractTypeNode
{
    /**
     * Constructs a new interface wrapper instance.
     *
     * @param \PDepend\Source\AST\ASTInterface $node
     */
    public function __construct(ASTInterface $node)
    {
        parent::__construct($node);
    }
}
