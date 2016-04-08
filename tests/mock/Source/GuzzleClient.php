<?php
namespace Crossjoin\Browscap\Tests\Mock\Source;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

/**
 * Class GuzzleClient
 *
 * @package Crossjoin\Browscap\Tests\Mock\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class GuzzleClient extends Client
{
    const MODE_SUCCESS = 0;
    const MODE_INTERNAL_EXCEPTION = 1;
    const MODE_INVALID_STATUS_CODE = 2;

    /**
     * @var int
     */
    protected $mode;

    /**
     * GuzzleClient constructor.
     *
     * @param int $mode
     */
    public function __construct($mode)
    {
        $this->mode = (int)$mode;

        parent::__construct([]);
    }

    public function request($method, $uri = null, array $options = [])
    {
        $result = parent::request($method, 'http://example.com', $options);

        if ($this->mode === self::MODE_INTERNAL_EXCEPTION) {
            throw new TransferException('Test exception.');
        } elseif ($this->mode === self::MODE_INVALID_STATUS_CODE) {
            $result = $result->withStatus('500');
        }

        return $result;
    }
}
