<?php
namespace Crossjoin\Browscap\Tests\Mock\Source\Ini;

use Crossjoin\Browscap\Exception\SourceConditionNotSatisfiedException;
use Crossjoin\Browscap\Source\None;

/**
 * Class Unavailable
 *
 * @package Crossjoin\Browscap\Tests\Mock\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Unavailable extends None
{
    /**
     * Unavailable constructor.
     *
     * @throws SourceConditionNotSatisfiedException
     */
    public function __construct()
    {
        throw new SourceConditionNotSatisfiedException('Test exception.');
    }
}
