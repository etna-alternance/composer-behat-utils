<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;
use PHP_CodeCoverage;
use PHP_CodeCoverage_Filter;
use PHP_CodeCoverage_Report_Clover;
use PHP_CodeCoverage_Report_HTML;
use PHP_CodeCoverage_Report_PHP;

trait Coverage
{
    static private $_coverage;

    /**
     * @BeforeSuite
     */
    static public function codeCoverageStart(SuiteEvent $event)
    {
        $parameters = $event->getContextParameters();

        switch (true) {
            case false === isset($parameters['enableCodeCoverage']):
            case true !== $parameters['enableCodeCoverage']:
                return;

            case false === isset($parameters['coveragePath']):
                throw new \Exception("Missing parameter");
        }

        $coverage_path = getcwd() . '/' . $parameters['coveragePath'];
        if (false === file_exists($coverage_path)) {
            mkdir($coverage_path, 0777, true);
        }

        $filter = new PHP_CodeCoverage_Filter();
        if (true === isset($parameters['blacklist']) && true === is_array($parameters['blacklist'])) {
            foreach ($parameters['blacklist'] as $blackElem) {
                $filter->addDirectoryToBlacklist(getcwd() . "/{$blackElem}");
            }
        }

        if (true === isset($parameters['whitelist']) && true === is_array($parameters['whitelist'])) {
            foreach ($parameters['whitelist'] as $whiteElem) {
                $filter->addDirectoryToWhitelist(getcwd() . "/{$whiteElem}");
            }
        }

        self::$_coverage = new PHP_CodeCoverage(null, $filter);
        self::$_coverage->start('Behat Test');
    }

    /**
     * @AfterSuite
     */
    static public function codeCoverageStop(SuiteEvent $event)
    {
        $parameters = $event->getContextParameters();

        switch (true) {
            case false === isset($parameters['enableCodeCoverage']):
            case true !== $parameters['enableCodeCoverage']:
                return;
        }

        self::$_coverage->stop();

        $writer = new PHP_CodeCoverage_Report_PHP;
        $writer->process(self::$_coverage, getcwd() . "/{$parameters['coveragePath']}.php");

        $writer = new PHP_CodeCoverage_Report_HTML;
        $writer->process(self::$_coverage, getcwd() . "/{$parameters['coveragePath']}");

        $writer = new PHP_CodeCoverage_Report_Clover();
        $writer->process(self::$_coverage, getcwd() . "/{$parameters['coveragePath']}.clover.xml");

        exec("open " . getcwd() . '/' . self::$_parameters['coveragePath'] . "/index.html");
    }
}
