<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;
use PHP_CodeCoverage_Filter;
use PHP_CodeCoverage;
use PHP_CodeCoverage_Report_PHP;
use PHP_CodeCoverage_Report_HTML;

trait Coverage
{
    static private $_coverage;

    /**
     * @BeforeSuite
     */
    static public function codeCoverageStart(SuiteEvent $event)
    {
        self::$_parameters = $event->getContextParameters();

        if (isset(self::$_parameters['enableCodeCoverage']) && self::$_parameters['enableCodeCoverage']) {
            if (!isset(self::$_parameters['coveragePath'])) {
                echo "No coveragePath provided\n";
                die("Error with Coverage : l." . (__LINE__ - 2));
            }
            if (!file_exists(getcwd() . '/' . self::$_parameters['coveragePath'])) {
                mkdir(getcwd() . '/' . self::$_parameters['coveragePath'], 0777, true);
            }

            $filter = new PHP_CodeCoverage_Filter();
            if (isset(self::$_parameters['blacklist']) && is_array(self::$_parameters['blacklist'])) {
                foreach (self::$_parameters['blacklist'] as $blackElem) {
                    $filter->addDirectoryToBlacklist(getcwd() . "/{$blackElem}");
                }
            }

            if (isset(self::$_parameters['whitelist']) && is_array(self::$_parameters['whitelist'])) {
                foreach (self::$_parameters['whitelist'] as $whiteElem) {
                    $filter->addDirectoryToWhitelist(getcwd() . "/{$whiteElem}");
                }
            }
            self::$_coverage = new PHP_CodeCoverage(null, $filter);
            self::$_coverage->start('Behat Test');
        }
    }

    /**
     * @AfterSuite
     */
    static public function codeCoverageStop()
    {
        if (isset(self::$_parameters['enableCodeCoverage']) && self::$_parameters['enableCodeCoverage']) {
            self::$_coverage->stop();

            $writer = new PHP_CodeCoverage_Report_PHP;
            $writer->process(self::$_coverage, getcwd() . '/' . self::$_parameters['coveragePath'] . microtime(true) . ".php");

            $writer = new PHP_CodeCoverage_Report_HTML;
            $writer->process(self::$_coverage, getcwd() . '/' . self::$_parameters['coveragePath']);

            exec("open " . getcwd() . '/' . self::$_parameters['coveragePath'] . "/index.html");
        }
    }
}
