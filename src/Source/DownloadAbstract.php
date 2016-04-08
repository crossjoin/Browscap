<?php
namespace Crossjoin\Browscap\Source;

use Crossjoin\Browscap\Browscap;
use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\SourceConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;

/**
 * Class DownloadAbstract
 *
 * @package Crossjoin\Browscap\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
abstract class DownloadAbstract
{
    /**
     * The format for the user agent to set for all requests made by the class
     */
    const USER_AGENT_FORMAT = '%libName %libVersion (%client)';

    /**
     * The URI to get the current Browscap data (in the configured format)
     *
     * @var string
     */
    protected $sourceUri;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Download constructor.
     *
     * @param string $sourceUri
     * @param array $clientOptions
     *
     * @throws InvalidArgumentException
     * @throws SourceConditionNotSatisfiedException
     */
    public function __construct($sourceUri, array $clientOptions = [])
    {
        if (!is_string($sourceUri)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($sourceUri) . "' for argument 'sourceUri'."
            );
        }
        
        // Set user agent
        if (!array_key_exists('headers', $clientOptions)) {
            $clientOptions['headers'] = [];
        }
        $clientOptions['headers']['User-Agent'] = $this->getUserAgent();

        $this->setClient(new Client($clientOptions));
        $this->setSourceUri($sourceUri);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    protected function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getSourceUri()
    {
        return $this->sourceUri;
    }

    /**
     * @param string $sourceUri
     *
     * @return DownloadAbstract
     * @throws InvalidArgumentException
     */
    protected function setSourceUri($sourceUri)
    {
        if (!is_string($sourceUri)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($sourceUri) . "' for argument 'sourceUri'."
            );
        }

        $this->sourceUri = $sourceUri;

        return $this;
    }

    /**
     * @return string
     */
    protected function getUserAgent()
    {
        return str_replace(
            array('%libName', '%libVersion', '%client'),
            array('Crossjoin\Browscap', Browscap::VERSION, 'GuzzleHttp'),
            self::USER_AGENT_FORMAT
        );
    }

    /**
     * @inheritdoc
     *
     * @throws SourceUnavailableException
     * @throws \RuntimeException
     */
    public function getContent()
    {
        $stream = $this->loadContent($this->getSourceUri());

        if ($stream->isReadable() === false) {
            throw new SourceUnavailableException('Source stream is not readable.', 1459162267);
        }

        while (($data = $stream->read(4096)) !== '') {
            yield $data;
        }
    }

    /**
     * @param $uri
     *
     * @return StreamInterface
     * @throws SourceUnavailableException
     */
    protected function loadContent($uri)
    {
        try {
            $response = $this->getClient()->request('GET', $uri);

            // Check status code
            if ($response->getStatusCode() !== 200) {
                throw new SourceUnavailableException(
                    'HTTP error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
                    1459162268
                );
            }

            return $response->getBody();
        } catch (GuzzleException $e) {
            throw new SourceUnavailableException(
                'Error loading the source, see previous exception for details.', 1459162269, $e
            );
        }
    }
}
