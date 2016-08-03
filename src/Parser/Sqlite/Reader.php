<?php
namespace Crossjoin\Browscap\Parser\Sqlite;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Parser\ReaderInterface;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterFactory;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterInterface;
use Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait;
use Crossjoin\Browscap\Source\Ini\GetRegExpForPatternTrait;
use Crossjoin\Browscap\Type;

/**
 * Class Reader
 *
 * @package Crossjoin\Browscap\Parser\Sqlite
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Reader implements ReaderInterface
{
    use DataDirectoryTrait;
    use DataVersionHashTrait;
    use PropertyFilterTrait;
    use GetRegExpForPatternTrait;
    
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $databaseFile;

    /**
     * @var int
     */
    protected $browserId;

    /**
     * @var int
     */
    protected $browserParentId;

    /**
     * @var array
     */
    protected $browserPatternKeywords;

    /**
     * @var string
     */
    protected $sqliteVersion;
    
    /**
     * Writer constructor.
     *
     * @param string $dataDirectory
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     */
    public function __construct($dataDirectory)
    {
        if (!is_string($dataDirectory)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($dataDirectory) . "' for argument 'dataDirectory'."
            );
        }

        $this->setDataDirectory($dataDirectory);
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    protected function getAdapter()
    {
        if ($this->adapter === null) {
            $databaseFile = $this->getDatabasePath();
            $adapter = AdapterFactory::getInstance($databaseFile);
            $this->setAdapter($adapter);
        }

        return $this->adapter;
    }

    /**
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     */
    protected function getDatabasePath()
    {
        $databasePath = $this->getDataDirectory() . DIRECTORY_SEPARATOR . $this->getDatabaseFileName();

        if (!$this->isFileReadable($databasePath)) {
            if (!file_exists($databasePath)) {
                throw new ParserRuntimeException(
                    "Linked database file '$databasePath' not found. Parser data need to be generated.",
                    1458898365
                );
            } else {
                throw new ParserRuntimeException("Linked database file '$databasePath' is not readable.", 1458898366);
            }
        } elseif (!is_file($databasePath)) {
            throw new ParserRuntimeException("Invalid database file name '$databasePath' in link file.", 1458898367);
        }

        return $databasePath;
    }

    /**
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     */
    protected function getDatabaseFileName()
    {
        // Get database to use, saved in the link file (as symlinks are not available or only
        // with admin permissions on Windows).
        $linkFile = $this->getDataDirectory() . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
        if ($this->isFileReadable($linkFile)) {
            return (string)file_get_contents($linkFile);
        } elseif (!file_exists($linkFile)) {
            throw new ParserRuntimeException(
                "Database link file '$linkFile' not found. Parser data need to be generated.",
                1458898368
            );
        } else {
            throw new ParserRuntimeException("Database link file '$linkFile' is not readable.", 1458898369);
        }
    }

    /**
     * @param $file
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function isFileReadable($file)
    {
        if (!is_string($file)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($file) . "' for argument 'file'."
            );
        }
        
        return is_readable($file);
    }

    /**
     * @param AdapterInterface $adapter
     */
    protected function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function isUpdateRequired()
    {
        try {
            $query = 'SELECT data_hash FROM info LIMIT 1';
            $dataHash = '';
            foreach ($this->getAdapter()->query($query) as $row) {
                $dataHash = $row['data_hash'];
            }
            $return = ($dataHash !== $this->getDataVersionHash());
        } catch (ParserConditionNotSatisfiedException $e) {
            $return = true;
        } catch (ParserRuntimeException $e) {
            $return = true;
        }

        return $return;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function getReleaseTime()
    {
        $query = 'SELECT release_time FROM info LIMIT 1';

        $releaseTime = 0;
        foreach ($this->getAdapter()->query($query) as $row) {
            $releaseTime = (int)$row['release_time'];
        }

        return $releaseTime;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function getVersion()
    {
        $query = 'SELECT version_id FROM info LIMIT 1';

        $versionId = 0;
        foreach ($this->getAdapter()->query($query) as $row) {
            $versionId = (int)$row['version_id'];
        }

        return $versionId;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function getType()
    {
        $query = 'SELECT type_id FROM info LIMIT 1';

        $typeId = Type::UNKNOWN;
        foreach ($this->getAdapter()->query($query) as $row) {
            $typeId = (int)$row['type_id'];
        }

        return $typeId;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function getBrowser($userAgent)
    {
        if (!is_string($userAgent)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($userAgent) . "' for argument 'userAgent'."
            );
        }

        // Init variables
        $browserId = $this->getBrowserId($userAgent);
        $browserParentId = $this->getBrowserParentId($userAgent);

        // Get all settings for the found browser
        //
        // It's best to use a recursive query here, but some linux distributions like CentOS and RHEL
        // use old sqlite library versions (like 3.6.20 or 3.7.17), so that we have to check the
        // version here and fall back to multiple single queries in case of older versions.
        if (version_compare($this->getSqliteVersion(), '3.8.3', '>=')) {
            $query  = 'SELECT t3.property_key, t4.property_value FROM (';
            $query .= 'WITH RECURSIVE browser_recursive(browser_id,browser_parent_id) AS (';
            $query .= 'SELECT browser_id, browser_parent_id FROM browser WHERE browser_id = :id';
            $query .= ' UNION ALL ';
            $query .= 'SELECT browser.browser_id, browser.browser_parent_id FROM browser, browser_recursive ';
            $query .= 'WHERE browser_recursive.browser_parent_id IS NOT NULL ';
            $query .= 'AND browser.browser_id = browser_recursive.browser_parent_id';
            $query .= ') ';
            $query .= 'SELECT MAX(t2.browser_id) AS browser_id, t2.property_key_id FROM browser_recursive t1 ';
            $query .= 'JOIN browser_property t2 ON t2.browser_id = t1.browser_id ';
            $query .= 'GROUP BY t2.property_key_id';
            $query .= ') t1 ';
            $query .= 'JOIN browser_property t2 ON t2.browser_id = t1.browser_id ';
            $query .= 'AND t2.property_key_id = t1.property_key_id ';
            $query .= 'JOIN browser_property_key t3 ON t3.property_key_id = t2.property_key_id ';
            $query .= 'JOIN browser_property_value t4 ON t4.property_value_id = t2.property_value_id';
            $query .= ' UNION ALL ';
            $query .= 'SELECT \'browser_name_pattern\' AS property_key, browser_pattern AS property_value ';
            $query .= 'FROM browser WHERE browser_id = :id';
            $query .= ' UNION ALL ';
            $query .= 'SELECT \'Parent\' AS property_key, browser_pattern AS property_value ';
            $query .= 'FROM browser WHERE browser_id = :parent';
            $statement = $this->getAdapter()->prepare($query);
        } else {
            $query = 'SELECT browser_id, browser_parent_id FROM browser WHERE browser_id = :id';
            $statement = $this->getAdapter()->prepare($query);
            $browserIds = [];
            $lastBrowserId = $browserId;
            while ($lastBrowserId !== null) {
                $result = $statement->execute(['id' => $lastBrowserId]);

                $lastBrowserId = null;
                if (count($result) > 0) {
                    $browserIds[] = (int)$result[0]['browser_id'];
                    if ($result[0]['browser_parent_id'] !== null) {
                        $lastBrowserId = (int)$result[0]['browser_parent_id'];
                    }
                }
            }

            $query  = 'SELECT t3.property_key, t4.property_value FROM (';
            $query .= 'SELECT MAX(browser_id) AS browser_id, property_key_id ';
            $query .= 'FROM browser_property WHERE browser_id IN (' . implode(', ', $browserIds) . ') ';
            $query .= 'GROUP BY property_key_id';
            $query .= ') t1 ';
            $query .= 'JOIN browser_property t2 ON t2.browser_id = t1.browser_id ';
            $query .= 'AND t2.property_key_id = t1.property_key_id ';
            $query .= 'JOIN browser_property_key t3 ON t3.property_key_id = t2.property_key_id ';
            $query .= 'JOIN browser_property_value t4 ON t4.property_value_id = t2.property_value_id';
            $query .= ' UNION ALL ';
            $query .= 'SELECT \'browser_name_pattern\' AS property_key, browser_pattern AS property_value ';
            $query .= 'FROM browser WHERE browser_id = :id';
            $query .= ' UNION ALL ';
            $query .= 'SELECT \'Parent\' AS property_key, browser_pattern AS property_value ';
            $query .= 'FROM browser WHERE browser_id = :parent';
            $statement = $this->getAdapter()->prepare($query);
        }

        $properties = [];
        foreach ($statement->execute(['id' => $browserId, 'parent' => $browserParentId]) as $row) {
            $properties[$row['property_key']] = $row['property_value'];
        }

        // Set regular expression properties
        $propertyFilter = $this->getPropertyFilter();
        if (!$propertyFilter->isFiltered('browser_name_regex')) {
            $properties['browser_name_regex'] = $this->getRegExpForPattern($properties['browser_name_pattern']);
        }
        if ($propertyFilter->isFiltered('browser_name_pattern')) {
            unset($properties['browser_name_pattern']);
        }
        if ($propertyFilter->isFiltered('Parent')) {
            unset($properties['Parent']);
        }

        // IMPORTANT: Reset browserId and browserParentId for next call
        $this->browserId = null;
        $this->browserParentId = null;

        // The settings are in random order, so sort them
        return $this->sortProperties($properties);
    }

    /**
     * @return string
     * @throws \Crossjoin\Browscap\Exception\UnexpectedValueException
     * @throws \Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException
     */
    protected function getSqliteVersion()
    {
        if ($this->sqliteVersion === null) {

            $this->sqliteVersion = '0.0.0';

            // Try to get the version number
            $query = 'SELECT sqlite_version() AS version';
            try {
                $result = $this->getAdapter()->query($query);
                if (count($result) > 0) {
                    $this->sqliteVersion = $result[0]['version'];
                }
            } catch (ParserRuntimeException $exception) {
                // Use default if version could not be detected
            }
        }

        return $this->sqliteVersion;
    }

    /**
     * @param string $userAgent
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    protected function getBrowserId($userAgent)
    {
        if ($this->browserId === null) {
            $this->findBrowser($userAgent);
        }

        return $this->browserId;
    }

    /**
     * @param string $userAgent
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    protected function getBrowserParentId($userAgent)
    {
        if ($this->browserParentId === null) {
            $this->findBrowser($userAgent);
        }

        return $this->browserParentId;
    }

    /**
     * @param string $userAgent
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    protected function findBrowser($userAgent)
    {
        if (!is_string($userAgent)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($userAgent) . "' for argument 'userAgent'."
            );
        }

        // Check each keyword table for the browser pattern
        $this->findBrowserInKeywordTables($userAgent);

        // If no match found in keyword tables, check the default table
        // (this also includes the '*' pattern for the default match).
        if ($this->browserId === null) {
            $this->findBrowserInDefaultTable($userAgent);
        }

        // Check if data found (the last step should always find the default settings)
        if ($this->browserId === null) {
            throw new ParserRuntimeException(
                "No result found for user agent '$userAgent'. There seems to be something wrong with the data."
            );
        }
    }

    /**
     * @param string $userAgent
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    protected function findBrowserInKeywordTables($userAgent)
    {
        if (!is_string($userAgent)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($userAgent) . "' for argument 'userAgent'."
            );
        }
        
        $query  = 'SELECT browser_id, browser_pattern_length FROM "search_[keyword]" ';
        $query .= 'WHERE browser_pattern_length >= :length AND :agent GLOB browser_pattern';

        $userAgentLowered = strtolower($userAgent);
        $maxLength = 0;
        $browserId = null;
        foreach ($this->getPatternKeywords() as $patternKeyword) {
            if (strpos($userAgentLowered, $patternKeyword) !== false) {
                $statement = $this->getAdapter()->prepare(str_replace('[keyword]', $patternKeyword, $query));

                /** @noinspection PdoApiUsageInspection */
                foreach ($statement->execute(['length' => $maxLength, 'agent' => $userAgentLowered]) as $row) {
                    $tmpLength = (int)$row['browser_pattern_length'];
                    $tmpBrowserId = (int)$row['browser_id'];

                    if ($tmpLength < $maxLength) {
                        continue; // @codeCoverageIgnore
                    }
                    if ($tmpLength === $maxLength && $browserId !== null && $tmpBrowserId < $browserId) {
                        continue; // @codeCoverageIgnore
                    }

                    $browserId = (int)$row['browser_id'];
                    $maxLength = $tmpLength;
                }
            }
        }

        if ($browserId !== null) {
            $this->browserId = $browserId;

            $query = 'SELECT browser_parent_id FROM browser WHERE browser_id = :id';
            $statement = $this->getAdapter()->prepare($query);
            /** @noinspection PdoApiUsageInspection */
            foreach ($statement->execute(['id' => $browserId]) as $row) {
                $this->browserParentId = (int)$row['browser_parent_id'];
            }
        }
    }

    /**
     * @param string $userAgent
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    protected function findBrowserInDefaultTable($userAgent)
    {
        if (!is_string($userAgent)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($userAgent) . "' for argument 'userAgent'."
            );
        }
        
        // Build query with default table
        $query  = 'SELECT t2.browser_id, t2.browser_parent_id FROM ';
        $query .= '(SELECT MIN(browser_id) AS browser_id FROM search WHERE :agent GLOB browser_pattern) t1 ';
        $query .= 'JOIN browser t2 ON t2.browser_id = t1.browser_id';
        $statement = $this->getAdapter()->prepare($query);

        /** @noinspection PdoApiUsageInspection */
        foreach ($statement->execute(['agent' => strtolower($userAgent)]) as $row) {
            $this->browserId = (int)$row['browser_id'];
            $this->browserParentId = (int)$row['browser_parent_id'];
        }
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    protected function getPatternKeywords()
    {
        if ($this->browserPatternKeywords === null) {
            $this->browserPatternKeywords = [];
            $query = 'SELECT keyword_value FROM keyword ORDER BY keyword_id';
            foreach ($this->getAdapter()->query($query) as $row) {
                $this->browserPatternKeywords[] = $row['keyword_value'];
            }
        }

        return $this->browserPatternKeywords;
    }

    /**
     * @param array $properties
     *
     * @return array
     */
    protected function sortProperties(array $properties)
    {
        ksort($properties);

        return $properties;
    }
}
