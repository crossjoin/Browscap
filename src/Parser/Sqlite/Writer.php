<?php
namespace Crossjoin\Browscap\Parser\Sqlite;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterFactory;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterInterface;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\PreparedStatementInterface;
use Crossjoin\Browscap\Parser\WriterInterface;
use Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait;
use Crossjoin\Browscap\Source\DataSet;
use Crossjoin\Browscap\Source\Ini\File;
use Crossjoin\Browscap\Source\Ini\ParseHeaderSectionTrait;
use Crossjoin\Browscap\Source\SourceInterface;

/**
 * Class Writer
 *
 * @package Crossjoin\Browscap\Parser\Sqlite
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Writer implements WriterInterface
{
    use DataDirectoryTrait;
    use DataVersionHashTrait;
    use PropertyFilterTrait;
    use ParseHeaderSectionTrait;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var SourceInterface
     */
    protected $source;

    /**
     * @var string
     */
    protected $temporaryFileName;

    /**
     * @var int[]
     */
    protected $primaryIds = [];

    /**
     * @var PreparedStatementInterface[]
     */
    protected $statements = [];

    /**
     * @var string[]
     */
    protected $parentPatterns = [];

    /**
     * @var string[]
     */
    protected $propertyNames = [];

    /**
     * @var string[]
     */
    protected $propertyValues = [];

    /**
     * @var string[]
     */
    protected $keywords = [];

    /**
     * Writer constructor.
     *
     * @param string $dataDirectory
     * @param SourceInterface $source
     *
     * @throws InvalidArgumentException
     */
    public function __construct($dataDirectory, SourceInterface $source)
    {
        if (!is_string($dataDirectory)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($dataDirectory) . "' for argument 'dataDirectory'."
            );
        }

        $this->setDataDirectory($dataDirectory);
        $this->setSource($source);
    }

    /**
     * @return SourceInterface
     */
    protected function getSource()
    {
        return $this->source;
    }

    /**
     * @param SourceInterface $source
     */
    protected function setSource(SourceInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    protected function getTemporaryFileName()
    {
        if ($this->temporaryFileName === null) {
            $this->temporaryFileName = $this->getDataDirectory() . DIRECTORY_SEPARATOR .
                'browscap_' . microtime(true) . '.sqlite';
        }

        return $this->temporaryFileName;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    protected function getAdapter()
    {
        if ($this->adapter === null) {
            $databaseFile = $this->getTemporaryFileName();
            $adapter = AdapterFactory::getInstance($databaseFile);
            $this->setAdapter($adapter);
        }

        return $this->adapter;
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
     * @throws ParserRuntimeException
     */
    public function generate()
    {
        // Disable time limit (generation is done within a few seconds, but if data have to be
        // downloaded it can take some more time)
        set_time_limit(0);

        $this->initializeDatabase();

        foreach ($this->getSource()->getDataSets() as $dataSet) {
            $this->processDataSet($dataSet);
        }

        $this->finalizeDatabase();

        return $this;
    }

    protected function initializeDatabase()
    {
        $adapter = $this->getAdapter();

        // Enable writes
        $adapter->exec('PRAGMA query_only = OFF');

        // Data generation optimization
        $adapter->exec('PRAGMA synchronous = OFF'); // Reduces time by about 35% - 40%
        $adapter->exec('PRAGMA journal_mode = OFF'); // Reduces time by about 10% - 15%
        $adapter->exec('PRAGMA cache_size = -70000'); // Reduces time by about 2% - 3%

        // Data query optimization
        $adapter->exec('PRAGMA temp_store = MEMORY');
        $adapter->exec('PRAGMA automatic_index = OFF');

        // Create tables
        $adapter->exec(
            'CREATE TABLE IF NOT EXISTS info (version_id INTEGER PRIMARY KEY ASC, release_time INTEGER NOT NULL, ' .
            'type_id INTEGER NOT NULL, data_hash TEXT NOT NULL)'
        );
        $adapter->exec(
            'CREATE TABLE IF NOT EXISTS browser (browser_id INTEGER PRIMARY KEY ASC, browser_parent_id INTEGER, ' .
            'browser_pattern TEXT NOT NULL)'
        );
        $adapter->exec(
            'CREATE TABLE IF NOT EXISTS search (browser_id INTEGER PRIMARY KEY ASC, ' .
            'browser_pattern_length INTEGER NOT NULL, browser_pattern TEXT NOT NULL)'
        );

        $adapter->exec(
            'CREATE TABLE IF NOT EXISTS browser_property (browser_property_id INTEGER PRIMARY KEY ASC, ' .
            'browser_id INTEGER NOT NULL, property_key_id INTEGER NOT NULL, property_value_id INTEGER NOT NULL)'
        );
        $adapter->exec(
            'CREATE TABLE IF NOT EXISTS browser_property_key (property_key_id INTEGER PRIMARY KEY ASC, ' .
            'property_key TEXT NOT NULL)'
        );
        $adapter->exec(
            'CREATE TABLE IF NOT EXISTS browser_property_value (property_value_id INTEGER PRIMARY KEY ASC, ' .
            'property_value TEXT NOT NULL)'
        );

        // Prepare insert statements
        $this->statements = [
            'info'          => $adapter->prepare('INSERT INTO info VALUES (:version, :time, :type, :hash)'),
            'browser'       => $adapter->prepare('INSERT INTO browser VALUES (:id, :parent, :pattern)'),
            'search'        => $adapter->prepare('INSERT INTO search VALUES (:id, :length, :pattern)'),
            'property'      => $adapter->prepare('INSERT INTO browser_property VALUES (NULL, :id, :key, :value)'),
            'propertyKey'   => $adapter->prepare('INSERT INTO browser_property_key VALUES (:id, :key)'),
            'propertyValue' => $adapter->prepare('INSERT INTO browser_property_value VALUES (:id, :value)'),
        ];

        $adapter->beginTransaction();
    }

    /**
     * @param string $tableName
     *
     * @return integer
     * @throws InvalidArgumentException
     */
    public function getNextId($tableName)
    {
        if (!is_string($tableName)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($tableName) . "' for argument 'tableName'."
            );
        }

        if (!array_key_exists($tableName, $this->primaryIds)) {
            $this->primaryIds[$tableName] = 1;
        } else {
            $this->primaryIds[$tableName]++;
        }

        return $this->primaryIds[$tableName];
    }

    /**
     * @param DataSet $dataSet
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     */
    protected function processDataSet(DataSet $dataSet)
    {
        if ($dataSet->getPattern() === File::HEADER_PATTERN) {
            $this->processHeader($dataSet);
        } else {
            $this->processBrowserData($dataSet);
        }
    }

    /**
     * @param DataSet $dataSet
     */
    protected function processHeader(DataSet $dataSet)
    {
        $result = $this->parseHeaderDataSet($dataSet);
        $versionId = $result['version'];
        $releaseTime = $result['released'];
        $typeId = $result['type'];

        $this->statements['info']->execute([
            'version' => $versionId,
            'time' => $releaseTime,
            'type' => $typeId,
            'hash' => $this->getDataVersionHash(),
        ]);
    }

    /**
     * @param DataSet $dataSet
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     */
    protected function processBrowserData(DataSet $dataSet)
    {
        // Get properties and filter them
        $pattern = $dataSet->getPattern();
        $propertiesOriginal = $dataSet->getProperties();
        $properties = $propertiesOriginal;
        $propertyFilter = $this->getPropertyFilter();
        foreach ($properties as $propertyName => $propertyValue) {
            if ($propertyFilter->isFiltered($propertyName)) {
                unset($properties[$propertyName]);
            }
        }
        $browserId = $this->getNextId('browser');

        // Check for placeholders
        $hasBrowscapPlaceholders = (strpos($pattern, '*') !== false || strpos($pattern, '?') !== false);

        // Parent patterns do not contain browscap placeholders, so we only need to save some of them for referencing.
        // (Use unmodified pattern here to find them later.)
        if ($hasBrowscapPlaceholders === false) {
            $this->addParentPattern($pattern, $browserId);
        }

        // Get parent id
        $parentId = $this->getParentPatternId($propertiesOriginal);

        // Get property ids (and generate new entries for new properties)
        $propertyIds = $this->getIdsForProperties($properties);

        // Filter the keywords from the pattern (all strings containing of the characters a-z,
        // with at least 4 characters) and count them to check for the most important keywords
        // later.
        if ($hasBrowscapPlaceholders === true) {
            $this->extractKeywordsFromPattern($pattern);
        }

        // Save browser entry
        $this->statements['browser']->execute(['id' => $browserId, 'parent' => $parentId, 'pattern' => $pattern]);

        // Optimization: Do not save patterns that are used as parents, assuming that every 'real' pattern
        // contains a browscap placeholder.
        //
        // We use the GLOB function in Sqlite, but this is case-sensitive. So we lower-case the pattern here
        // (and later, when searching for it).
        if ($hasBrowscapPlaceholders === true) {
            $this->statements['search']->execute([
                    'id' => $browserId,
                    'length' => strlen(str_replace('*', '', $pattern)),
                    'pattern' => strtolower($pattern)]
            );
        }

        // Save all properties for the current pattern
        foreach ($propertyIds as $keyId => $valueId) {
            $this->statements['property']->execute(['id' => $browserId, 'key' => $keyId, 'value' => $valueId]);
        }
    }

    /**
     * @param string $pattern
     * @param int $browserId
     *
     * @throws InvalidArgumentException
     */
    protected function addParentPattern($pattern, $browserId)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($pattern) . "' for argument 'pattern'."
            );
        }
        if (!is_int($browserId)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($browserId) . "' for argument 'browserId'."
            );
        }

        $this->parentPatterns[$pattern] = $browserId;
    }

    /**
     * @param array $properties
     *
     * @return int|null
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     */
    protected function getParentPatternId(array &$properties)
    {
        $parentId = null;
        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (isset($properties['Parent'])) {
            if (array_key_exists($properties['Parent'], $this->parentPatterns)) {
                $parentId = $this->parentPatterns[$properties['Parent']];
                unset($properties['Parent']);
            } else {
                throw new ParserRuntimeException("Parent '" . $properties['Parent'] . "' not found.");
            }
        }

        return $parentId;
    }

    /**
     * @param array $properties
     *
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getIdsForProperties(array $properties)
    {
        $browserPropertyIds = [];

        foreach ($properties as $propertyKey => $propertyValue) {
            if ($propertyKey === 'Parent') {
                continue;
            }
            if (!array_key_exists($propertyKey, $this->propertyNames)) {
                $propertyKeyId = $this->getNextId('property_key');
                $this->propertyNames[$propertyKey] = $propertyKeyId;

                $this->statements['propertyKey']->execute([
                    'id' => $propertyKeyId,
                    'key' => $propertyKey,
                ]);
            }

            if (!array_key_exists($propertyValue, $this->propertyValues)) {
                $propertyValueId = $this->getNextId('property_value');
                $this->propertyValues[$propertyValue] = $propertyValueId;

                $this->statements['propertyValue']->execute([
                    'id' => $propertyValueId,
                    'value' => $propertyValue,
                ]);
            }

            $propertyKeyId = $this->propertyNames[$propertyKey];
            $propertyValueId = $this->propertyValues[$propertyValue];

            $browserPropertyIds[$propertyKeyId] = $propertyValueId;
        }

        return $browserPropertyIds;
    }

    /**
     * @param string $pattern
     *
     * @throws InvalidArgumentException
     */
    protected function extractKeywordsFromPattern($pattern)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($pattern) . "' for argument 'pattern'."
            );
        }

        // Filter the keywords from the pattern (all strings containing of the characters a-z,
        // with at least 4 characters) and count them to check for the most important keywords
        // later.
        preg_match_all('#[a-z][a-z0-9]{3,}#', strtolower($pattern), $matches);
        foreach ($matches[0] as $keyword) {
            $this->keywords[$keyword] = array_key_exists($keyword, $this->keywords) ? ++$this->keywords[$keyword] : 1;
        }
    }

    protected function finalizeDatabase()
    {
        $adapter = $this->getAdapter();

        // Commit transaction (has to be done before changing the structure for search optimization)
        $adapter->commitTransaction();

        // Optimizations
        $this->generateKeywordSearchTables();
        $this->createIndexes();
        $this->optimizeTableForReading();

        // Replace the link to the table with the new one
        $this->saveLink();

        // Delete old database files
        $this->cleanUp();
    }

    protected function createIndexes()
    {
        $adapter = $this->getAdapter();

        $adapter->exec(
            'CREATE UNIQUE INDEX IF NOT EXISTS u_brpr_brid_prkeid ON browser_property(browser_id, property_key_id)'
        );
        $adapter->exec('CREATE INDEX IF NOT EXISTS i_se_brpale ON search (browser_pattern_length)');
    }

    protected function generateKeywordSearchTables()
    {
        $adapter = $this->getAdapter();

        // Create keyword table
        $adapter->exec(
            'CREATE TABLE IF NOT EXISTS keyword (keyword_id INTEGER PRIMARY KEY ASC, keyword_value TEXT NOT NULL)'
        );

        // Use only the top keywords - if we'd use all, this would create thousands of tables,
        // which would be very bad for the performance.
        arsort($this->keywords);
        $keywords = array_slice($this->keywords, 0, 100);

        // Important: Now re-sort the array again to start with the most used keywords having the lowest count
        asort($keywords);

        $keywordId = 1;
        foreach ($keywords as $keywordValue => $keywordCount) {
            // Do NOT use "CREATE TABLE ... AS" here, because this would automatically add an extra id column,
            // which requires additional space
            $adapter->exec(
                'CREATE TABLE IF NOT EXISTS "search_' . $keywordValue . '" ' .
                '(browser_id INTEGER PRIMARY KEY ASC, browser_pattern_length INTEGER NOT NULL, ' .
                'browser_pattern TEXT NOT NULL)'
            );
            /** @noinspection DisconnectedForeachInstructionInspection */
            $adapter->beginTransaction();
            $adapter->exec(
                'INSERT INTO "search_' . $keywordValue . '" ' .
                'SELECT browser_id, browser_pattern_length, browser_pattern ' .
                "FROM search WHERE browser_pattern GLOB '*$keywordValue*'"
            );
            $adapter->exec("INSERT INTO keyword VALUES ($keywordId, '$keywordValue')");
            $adapter->exec("DELETE FROM search WHERE browser_pattern GLOB '*$keywordValue*'");
            /** @noinspection DisconnectedForeachInstructionInspection */
            $adapter->commitTransaction();

            $adapter->exec(
                'CREATE INDEX IF NOT EXISTS i_se' . $keywordId . '_brpale ON "search_' . $keywordValue .
                '"(browser_pattern_length)'
            );

            $keywordId++;
        }
    }

    protected function optimizeTableForReading()
    {
        $adapter = $this->getAdapter();

        $adapter->exec('VACUUM');
        $adapter->exec('PRAGMA query_only = ON');
    }

    /**
     * @throws ParserRuntimeException
     */
    protected function saveLink()
    {
        // Update the link to the new database
        $linkFile = $this->getLinkPath();
        $databaseFile = basename($this->getTemporaryFileName());
        if (@file_put_contents($linkFile, $databaseFile) === false) {
            throw new ParserRuntimeException("Could not create/update link file '$linkFile'.");
        }
    }

    protected function cleanUp()
    {
        $currentDatabaseFile = basename($this->getTemporaryFileName());
        foreach (glob($this->getDataDirectory() . DIRECTORY_SEPARATOR . 'browscap_*.sqlite') as $file) {
            if (basename($file) !== $currentDatabaseFile) {
                @unlink($file);
            }
        }
    }

    /**
     * @return string
     */
    protected function getLinkPath()
    {
        return $this->getDataDirectory() . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
    }
}
