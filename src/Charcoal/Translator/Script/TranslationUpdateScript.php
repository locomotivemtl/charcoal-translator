<?php

namespace Charcoal\Translator\Script;

use InvalidArgumentException;

// From Pimple
use Pimple\Container;

// From 'symfony/translation'
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

// From 'charcoal-translator'
use Charcoal\Translator\Script\AbstractTranslationScript;

/**
 * Script that parses templates to extract translation messages into the translation files.
 *
 * Based on {@see Symfony\Bundle\FrameworkBundle\Command\TranslationUpdateCommand}.
 */
class TranslationUpdateScript extends AbstractTranslationScript
{
    /**
     * Translation writer.
     *
     * @var TranslationWriterInterface
     */
    private $writer;

    /**
     * Translation reader.
     *
     * @var TranslationReaderInterface
     */
    private $reader;

    /**
     * Translation extractor.
     *
     * @var ExtractorInterface
     */
    private $extractor;

    /**
     * Message selector.
     *
     * @var MessageSelector
     */
    private $selector;

    /**
     * Default locale.
     *
     * @var string|null
     */
    private $defaultLocale;

    /**
     * Default translations path.
     *
     * @var string|null
     */
    private $defaultTransPath;

    /**
     * Default views path.
     *
     * @var string|null
     */
    private $defaultViewsPath;

    /**
     * The base path for the Charcoal installation.
     *
     * @var string|null
     */
    private $basePath;

    /**
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->setDescription(
            'The <underline>translation/update</underline> script '.
            'extracts translation strings from templates. '.
            'Translations can be displayed or merged into translation files'
        );
    }

    /**
     * @throws InvalidArgumentException If the command arguments are invalid.
     * @return void
     */
    protected function execute()
    {
        $cli   = $this->climate();
        $args  = $cli->arguments;
        $quiet = $this->quiet();

        $cli->br();
        $cli->bold()->underline()->out('Updates the translation file');
        $cli->br();

        // Check presence of "force" or "dump-message"
        if (!$args->defined('force') && !$args->defined('dump_messages')) {
            throw new InvalidArgumentException(
                'You must choose one of --force or --dump-messages'
            );
        }

        // Check "output-format"
        $outputFormat = $args->get('output_format');
        $supportedFormats = $this->writer->getFormats();
        if (!in_array($outputFormat, $supportedFormats)) {
            throw new InvalidArgumentException(sprintf(
                'Wrong output format, must be one of: %s',
                implode(', ', $supportedFormats).'.'
            ));
        }

        $basePath = rtrim($this->basePath, '/');

        // Define Root Paths
        $transPaths = [ $basePath.'/translations' ];
        if ($this->defaultTransPath) {
            $transPaths[] = $this->defaultTransPath;
        }

        $viewsPaths = [ $basePath.'/templates', $basePath.'/views', $basePath.'/src' ];
        if ($this->defaultViewsPath) {
            $viewsPaths[] = $this->defaultViewsPath;
        }

        $currentName = 'Project';

        // Override with provided Bundle info
        $outputPath = rtrim($args->get('output_path'), '/');
        if (!empty($outputPath)) {
            $targetPath = $basePath.'/'.$outputPath;

            if (!is_dir($targetPath)) {
                throw new InvalidArgumentException(sprintf(
                    'Bad output path: %s',
                    $outputPath
                ));
            }

            $transPaths  = [ $targetPath.'/translations' ];
            $viewsPaths  = [ $targetPath.'/templates', $targetPath.'/views', $targetPath.'/src' ];
            $currentName = $outputPath;
        }

        if (!$quiet) {
            $cli->title('Translation Messages Extractor and Dumper');
            $cli->comment(sprintf(
                'Generating "<info>%s</info>" translation files for "<info>%s</info>"',
                $args->get('locale'),
                $currentName
            ));
        }

        // load any messages from templates
        $extractedCatalogue = new MessageCatalogue($args->get('locale'));
        if (!$quiet) {
            $cli->comment('Parsing templates…');
        }
        $this->extractor->setPrefix($args->get('prefix'));
        foreach ($viewsPaths as $path) {
            if (is_dir($path)) {
                $this->extractor->extract($path, $extractedCatalogue);
            }
        }

        // load any existing messages from the translation files
        $currentCatalogue = new MessageCatalogue($args->get('locale'));
        if (!$quiet) {
            $cli->comment('Loading translation files…');
        }
        foreach ($transPaths as $path) {
            if (is_dir($path)) {
                $this->reader->read($path, $currentCatalogue);
            }
        }

        if (null !== $domain = $args->get('domain')) {
            $currentCatalogue   = $this->filterCatalogue($currentCatalogue, $domain);
            $extractedCatalogue = $this->filterCatalogue($extractedCatalogue, $domain);
        }

        // process catalogues
        $operation = $args->get('clean')
                   ? new TargetOperation($currentCatalogue, $extractedCatalogue)
                   : new MergeOperation($currentCatalogue, $extractedCatalogue);

        // Exit if no messages found.
        if (!count($operation->getDomains())) {
            if (!$quiet) {
                $cli->error('No translation messages were found.');
            }
            return;
        }

        $resultDescriptor = 'Translation files were successfully updated';

        if ($args->defined('dump_messages')) {
            $cli->br();

            $extractedMessagesCount = 0;
            foreach ($operation->getDomains() as $domain) {
                $newKeys = array_keys($operation->getNewMessages($domain));
                $allKeys = array_keys($operation->getMessages($domain));

                $list = array_merge(
                    array_diff($allKeys, $newKeys),
                    array_map(function ($id) {
                        return sprintf('<fg=green>%s</>', $id);
                    }, $newKeys),
                    array_map(function ($id) {
                        return sprintf('<fg=red>%s</>', $id);
                    }, array_keys($operation->getObsoleteMessages($domain)))
                );

                $domainMessagesCount = count($list);

                $dumpDescriptor = sprintf(
                    'Messages extracted for domain "<info>%s</info>" (%s)',
                    $domain,
                    $this->selector->choose(
                        '%count% message|%count% messages',
                        $domainMessagesCount,
                        'en'
                    )
                );

                $io->info($dumpDescriptor);
                $io->table($list);

                $extractedMessagesCount += $domainMessagesCount;
            }

            if (!$quiet) {
                if ($args->get('output_format') === 'xlf') {
                    $cli->comment('XLIFF output version is <bold>1.2</bold>');
                }
            }

            $resultDescriptor = $this->selector->choose(
                '%count% message was successfully extracted|%count% messages were successfully extracted',
                $extractedMessagesCount,
                'en'
            );
        }

        if ($args->defined('no-backup')) {
            $this->writer->disableBackup();
        }

        if ($args->defined('force')) {
            if (!$quiet) {
                $cli->comment('Writing files…');
            }

            $bundleTransPath = false;
            foreach ($transPaths as $path) {
                if (is_dir($path)) {
                    $bundleTransPath = $path;
                }
            }

            if (!$bundleTransPath) {
                $bundleTransPath = end($transPaths);
            }

            $this->writer->write($operation->getResult(), $outputFormat, [
                'path'           => $bundleTransPath,
                'default_locale' => $this->defaultLocale,
            ]);

            if ($args->defined('dump_messages')) {
                $resultDescriptor .= ' and translation files were updated';
            }
        }

        if (!$quiet) {
            $cli->br();
            $cli->info($resultDescriptor.'.');
        }
    }



    // CLI Arguments
    // =========================================================================

    /**
     * Retrieve the script's primary arguments.
     *
     * @return array
     */
    protected function providedArguments()
    {
        $validateLocale = function ($response) {
            return in_array($response, $this->getLocales());
        };

        $args = [
            'locale' => [
                'longPrefix'   => 'locale',
                'description'  => 'The locale.',
                'acceptValue'  => $validateLocale->bindTo($this),
                'required'     => true,
            ],
            'domain' => [
                'longPrefix'   => 'domain',
                'description'  => 'The domain to update.',
            ],
            'output_path' => [
                'longPrefix'   => 'output-path',
                'description'  => 'The directory where to load and update the messages.',
            ],
            'output_format' => [
                'longPrefix'   => 'output-format',
                'description'  => 'Override the default output format.',
                'defaultValue' => 'csv',
            ],
            'prefix' => [
                'longPrefix'   => 'prefix',
                'description'  => 'Add a prefix the messages for new translation strings.',
            ],
            'dump_messages' => [
                'longPrefix'   => 'dump-messages',
                'description'  => 'Dump the messages in the console.',
                'noValue'      => true,
            ],
            'force' => [
                'longPrefix'   => 'force',
                'description'  => 'Update the translation file(s).',
                'noValue'      => true,
            ],
            'no_backup' => [
                'longPrefix'   => 'no-backup',
                'description'  => 'Disable backups for translation files.',
                'noValue'      => true,
            ],
            'clean' => [
                'longPrefix'   => 'clean',
                'description'  => 'Purge messages not found in templates.',
                'noValue'      => true,
            ],
        ];

        return $args;
    }

    /**
     * Filter a message catalogue by domain.
     *
     * @param  MessageCatalogue $catalogue The message catalogue to filter.
     * @param  string           $domain    The domain to filter by.
     * @return MessageCatalogue New message catalogue with filtered messages from $catalogue.
     */
    private function filterCatalogue(MessageCatalogue $catalogue, string $domain)
    {
        $filteredCatalogue = new MessageCatalogue($catalogue->getLocale());

        if ($messages = $catalogue->all($domain)) {
            $filteredCatalogue->add($messages, $domain);
        }

        foreach ($catalogue->getResources() as $resource) {
            $filteredCatalogue->addResource($resource);
        }

        if ($metadata = $catalogue->getMetadata('', $domain)) {
            foreach ($metadata as $k => $v) {
                $filteredCatalogue->setMetadata($k, $v, $domain);
            }
        }

        return $filteredCatalogue;
    }


    // Dependencies
    // =========================================================================

    /**
     * Set dependencies from the service locator.
     *
     * @param  Container $container A service locator.
     * @return void
     */
    protected function setDependencies(Container $container)
    {
        $this->writer           = $container['translator/writer'];
        $this->reader           = $container['translator/reader'];
        $this->extractor        = $container['translator/extractor'];
        $this->selector         = $container['translator/message-selector'];
        $this->defaultLocale    = $container['translator/default-locale'];
        $this->defaultTransPath = $container['translator/default-trans-path'];
        $this->defaultViewsPath = $container['translator/default-views-path'];
    }
}
