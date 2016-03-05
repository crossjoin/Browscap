<?php
namespace Crossjoin\Browscap\Updater;

/**
 * Local updater class
 *
 * This class loads the source data from a local file, which need to be set
 * via the options.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class Local extends AbstractUpdater
{
    /**
     * Local constructor.
     *
     * @param null $options
     * @throws \InvalidArgumentException
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        // Set update method
        $this->updateMethod = 'local';

        // Add additional options
        $this->options['LocalFile'] = null;
    }

    /**
     * Gets the current browscap version (time stamp)
     *
     * @return int
     * @throws \RuntimeException
     */
    public function getBrowscapVersion()
    {
        $file = $this->getOption('LocalFile');
        if ($file === null) {
            throw new \RuntimeException("Option 'LocalFile' not set.");
        }
        if (!is_readable($file)) {
            throw new \RuntimeException("File '$file' set in option 'LocalFile' is not readable.");
        }
        return (int)filemtime($file);
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
     * @throws \RuntimeException
     */
    public function getBrowscapSource()
    {
        $file = $this->getOption('LocalFile');
        if ($file === null) {
            throw new \RuntimeException("Option 'LocalFile' not set.");
        }
        if (!is_readable($file)) {
            throw new \RuntimeException("File '$file' set in option 'LocalFile' is not readable.");
        }
        return file_get_contents($file);
    }
}
