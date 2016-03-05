<?php
namespace Crossjoin\Browscap\Updater;

/**
 * Updater factory class
 *
 * This class checks the current settings and returns the best matching
 * updater instance (except the local updater, which requires additional
 * settings and can therefore only be set manually).
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class FactoryUpdater
{
    /**
     * Get a available updater instance, or returns NULL is none available.
     *
     * @return \Crossjoin\Browscap\Updater\AbstractUpdater
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getInstance()
    {
        if (function_exists('curl_init')) {
            return new Curl();
        } elseif ((bool)(int)ini_get('allow_url_fopen') !== false) {
            return new FileGetContents();
        } elseif (($browscapFile = (string)ini_get('browscap')) !== '') {
            $updater = new Local();
            $updater->setOption('LocalFile', $browscapFile);
            return $updater;
        }
        
        return new None();
    }
}
