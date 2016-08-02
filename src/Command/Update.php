<?php
namespace Crossjoin\Browscap\Command;

use Crossjoin\Browscap\Browscap;
use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\SourceConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Parser\Sqlite\Parser;
use Crossjoin\Browscap\PropertyFilter\Allowed;
use Crossjoin\Browscap\PropertyFilter\Disallowed;
use Crossjoin\Browscap\Source\Ini\BrowscapOrg;
use Crossjoin\Browscap\Source\Ini\File;
use Crossjoin\Browscap\Source\Ini\PhpSetting;
use Crossjoin\Browscap\Type;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Update
 *
 * @package Crossjoin\Browscap\Command
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 * @codeCoverageIgnore
 */
class Update extends Command
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update the parser data')
            ->addOption(
                'dir',
                null,
                InputArgument::OPTIONAL,
                'Directory to save parser data in (sub directory will be generated automatically).'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Flag to force an update although it seems to be unnecessary.'
            )
            ->addOption(
                'ini-php',
                null,
                InputOption::VALUE_NONE,
                'Will use the settings of the browscap directive in the php.ini file for the update.'
            )
            ->addOption(
                'ini-load',
                null,
                InputOption::VALUE_OPTIONAL,
                'Will download the parser data (of the given type - "lite", "full" or "standard") for the update. ' .
                'If no type set, the "standard" type is used.'
            )
            ->addOption(
                'ini-file',
                null,
                InputOption::VALUE_REQUIRED,
                'Will use the defined browscap ini-file for the update.'
            )
            ->addOption(
                'filter-allowed',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Sets a filter to define allowed properties when generating the data. Other properties will be ignored.'
            )
            ->addOption(
                'filter-disallowed',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Sets a filter to define disallowed properties when generating the data. Other properties are allowed.'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws UnexpectedValueException
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        if (((int)$input->getOption('ini-php') + (int)$input->getOption('ini-load') + (int)$input->getOption('ini-file')) > 1) {
            throw new UnexpectedValueException('Please use only one --ini-* option to set the data source.');
        }
        if (((count($input->getOption('filter-allowed')) > 0) + (count($input->getOption('filter-disallowed')) > 0)) > 1) {
            throw new UnexpectedValueException('Please use only one --filter-* option for the data source.');
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     * @throws SourceConditionNotSatisfiedException
     * @throws SourceUnavailableException
     * @throws UnexpectedValueException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $browscap = new Browscap();

        // Set source
        $source = null;
        if ($input->getOption('ini-php')) {
            $source = new PhpSetting();
        } elseif ($input->getOption('ini-load')) {
            $type = Type::STANDARD;
            switch ($input->getOption('ini-load')) {
                case 'lite':
                    $type = Type::LITE;
                    break;
                case 'full':
                    $type = Type::FULL;
                    break;
            }
            $source = new BrowscapOrg($type);
        } elseif ($input->getOption('ini-file')) {
            $file = (string)$input->getOption('ini-file');
            $source = new File($file);
        }
        if ($source !== null) {
            $browscap->getParser()->setSource($source);
        }
        
        // Set filter
        $filter = null;
        if ($input->getOption('filter-allowed')) {
            $properties = (array)$input->getOption('filter-allowed');
            $filter = new Allowed($properties);
        } elseif ($input->getOption('filter-disallowed')) {
            $properties = (array)$input->getOption('filter-disallowed');
            $filter = new Disallowed($properties);
        }
        if ($filter !== null) {
            $browscap->getParser()->setPropertyFilter($filter);
        }

        // Set data directory
        if ($input->getOption('dir')) {
            /** @var Parser $parser */
            $parser = $browscap->getParser();
            $parser->setDataDirectory((string)$input->getOption('dir'));
        }

        // Force an update?
        $force = false;
        if ($input->getOption('force')) {
            $force = true;
        }

        // Process update
        $output->writeln('Processing update...');
        $updated = $browscap->update($force);

        // Output result
        $version = $browscap->getParser()->getReader()->getVersion();
        $type    = $browscap->getParser()->getReader()->getType();
        $name    = Type::getName($type);
        if ($updated === true) {
            $output->writeln(sprintf('Browscap data successfully updated. New version: %s (%s)', $version, $name));
        } else {
            $output->writeln(sprintf('Nothing to update. Current version: %s (%s)', $version, $name));
        }
    }
}
