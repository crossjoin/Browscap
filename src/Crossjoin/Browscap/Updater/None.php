<?php
namespace Crossjoin\Browscap\Updater;

/**
 * None updater class
 *
 * This updater does nothing, so if you set it, the source data won't be updated.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class None extends AbstractUpdater
{
    /**
     * None constructor.
     *
     * @param null $options
     * @throws \InvalidArgumentException
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        // Set update method
        $this->updateMethod = 'none';
    }

    /**
     * Gets the current browscap version (time stamp)
     *
     * @return int
     */
    public function getBrowscapVersion()
    {
        return 0;
    }

    /**
     * Gets the current browscap version number (if possible for the source)
     *
     * @return int|null
     */
    public function getBrowscapVersionNumber()
    {
        return null;
    }

    /**
     * Gets the browscap data of the used source type
     *
     * @return string|boolean
     */
    public function getBrowscapSource()
    {
        return false;
    }
}
