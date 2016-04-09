<?php
namespace Crossjoin\Browscap;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Formatter\FormatterInterface;
use Crossjoin\Browscap\Formatter\PhpGetBrowser;
use Crossjoin\Browscap\Parser\ParserInterface;
use Crossjoin\Browscap\Parser\Sqlite\Parser;

/**
 * Class Browscap
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Browscap
{
    const VERSION = '2.0.0';

    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    /**
     * Update probability percentage, so a value of 1 would check for updates in
     * 1% of the requests. A value of 0 disables automatic updates (default).
     *
     * @var int
     */
    protected $autoUpdateProbability = 0;

    /**
     * @return ParserInterface
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     */
    public function getParser()
    {
        if ($this->parser === null) {
            $this->setParser(new Parser());
        }

        return $this->parser;
    }

    /**
     * @param ParserInterface $parser
     *
     * @return Browscap
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * @return FormatterInterface
     * @throws InvalidArgumentException
     */
    public function getFormatter()
    {
        if ($this->formatter === null) {
            $this->setFormatter(new PhpGetBrowser());
        }

        return $this->formatter;
    }

    /**
     * @param FormatterInterface $formatter
     *
     * @return Browscap
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return int
     */
    public function getAutoUpdateProbability()
    {
        return $this->autoUpdateProbability;
    }

    /**
     * @param int $autoUpdateProbability
     *
     * @return Browscap
     * @throws InvalidArgumentException
     */
    public function setAutoUpdateProbability($autoUpdateProbability)
    {
        if (!is_int($autoUpdateProbability)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($autoUpdateProbability) . "' for argument 'autoUpdateProbability'."
            );
        }

        $this->autoUpdateProbability = $autoUpdateProbability;

        return $this;
    }

    /**
     * @param string|null $userAgent
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function getBrowser($userAgent = null)
    {
        if (!is_string($userAgent) && $userAgent !== null) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($userAgent) . "' for argument 'userAgent'."
            );
        }
        
        // Check if an automatic update is required
        $this->autoUpdate();

        // If no user agent is set, try to get it from the HTTP headers
        if ($userAgent === null) {
            $userAgent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }

        // Get rwa Browscap data and return a formatted version, using the set Formatter
        $browscapData = $this->getParser()->getReader()->getBrowser($userAgent);
        return $this->getFormatter()->format($browscapData);
    }

    /**
     * @return Browscap
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    protected function autoUpdate()
    {
        $updateProbability = $this->getAutoUpdateProbability();
        if ($updateProbability >= mt_rand(1, 100) || $this->getParser()->getReader()->isUpdateRequired()) {
            $this->update();
        }

        return $this;
    }

    /**
     * Updates Browscap data. Returns a boolean indicating if an update has been done or
     * not (because not required). If the option 'forceUpdate' is used, the update is always
     * done, no matter if required or not.
     *
     * @param bool $forceUpdate
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function update($forceUpdate = false)
    {
        if (!is_bool($forceUpdate)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($forceUpdate) . "' for argument 'forceUpdate'."
            );
        }
        
        $parser = $this->getParser();

        // Check if an update is required, either because it's forced, or the source is newer
        $updateRequired = $forceUpdate;
        if ($updateRequired === false) {
            $reader = $parser->getReader();
            $source = $parser->getSource();
            $updateRequired = (
                $reader->isUpdateRequired() ||
                $reader->getType() !== $source->getType() ||
                $reader->getVersion() < $source->getVersion()
            );
        }

        // Generate ne parser data, if an update is required
        if ($updateRequired === true) {
            $parser->getWriter()->generate();

            // Re-initiate the reader and check if the update is still required,
            // if so, there's something wrong with the generation of the data.
            $reader = $parser->getReader(true);
            if ($reader->isUpdateRequired() === true) {
                throw new ParserRuntimeException(
                    'There is something wrong with the parser. The data have been re-generated without errors, ' .
                    'but the reader still requires an update of the data...'
                );
            }
        }

        return $updateRequired;
    }

    /**
     * Use the class instance as a function that exactly behaves like PHP get_browser().
     *
     * @param string $userAgent
     * @param bool $returnArray
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function __invoke($userAgent, $returnArray = false)
    {
        if (!is_string($userAgent)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($userAgent) . "' for argument 'userAgent'."
            );
        }
        if (!is_bool($returnArray)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($returnArray) . "' for argument 'returnArray'."
            );
        }

        $this->setFormatter(new PhpGetBrowser($returnArray));
        return $this->getBrowser($userAgent);
    }
}
