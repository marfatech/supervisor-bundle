<?php

declare(strict_types=1);

/*
 * This file is part of the SupervisorBundle package.
 *
 * (c) Marfatech <https://marfa-tech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Marfatech\Bundle\SupervisorBundle\Dto;

class ConfigDto
{
    /**
     * @var string|null
     */
    public $server;

    /**
     * @var string|null
     */
    public $executor;

    /**
     * @var string|null
     */
    public $console;

    /**
     * @var array
     */
    public $options = [];
}
