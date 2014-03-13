<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;
use PHP_CodeCoverage_Filter;
use PHP_CodeCoverage;
use PHP_CodeCoverage_Report_PHP;
use PHP_CodeCoverage_Report_HTML;

trait BehatCoverage
{
    static private $_coverage;
    static private $_parameters;

    /**
     * @BeforeSuite
     */
    static public function codeCoverageStart(SuiteEvent $event)
    {
        self::$_parameters = $event->getContextParameters();

        if (isset(self::$_parameters['enableCodeCoverage']) && self::$_parameters['enableCodeCoverage']) {
            $filter = new PHP_CodeCoverage_Filter();
            if (isset(self::$_parameters['blacklist']) && is_array(self::$_parameters['blacklist'])) {
                foreach (self::$_parameters['blacklist'] as $blackElem) {
                    $filter->addDirectoryToBlacklist(__DIR__ . "/{$blackElem}");
                }
            }

            if (isset(self::$_parameters['whitelist']) && is_array(self::$_parameters['whitelist'])) {
                foreach (self::$_parameters['whitelist'] as $whiteElem) {
                    $filter->addDirectoryToWhitelist(__DIR__ . "/{$whiteElem}");
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
            $writer->process(self::$_coverage, __DIR__ . '/' . self::$_parameters['coveragePath'] . microtime(true) . ".php");

            $writer = new PHP_CodeCoverage_Report_HTML;
            $writer->process(self::$_coverage, __DIR__ . '/' . self::$_parameters['coveragePath']);
        }
    }
}
