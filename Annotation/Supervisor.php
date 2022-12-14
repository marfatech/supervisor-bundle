<?php

declare(strict_types=1);

/*
 * This file is part of the SupervisorBundle package.
 *
 * (c) Wakeapp <https://wakeapp.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Marfatech\Bundle\SupervisorBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Supervisor
{
    /**
     * @var string
     */
    public $executor;

    /**
     * @var integer
     */
    public $delayBefore;

    /**
     * @var integer
     */
    public $delayAfter;

    /**
     * @var string
     */
    public $console;

    /**
     * @var string
     */
    public $programName;

    /**
     * @var string
     */
    public $commandName;

    /**
     * @var integer
     */
    public $processes;

    /**
     * @var string
     */
    public $params;

    /**
     * @var string
     */
    public $server;

    /**
     * @var array
     */
    public $options = [];
}
