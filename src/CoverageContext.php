<?php

namespace ETNA\FeatureContext;

use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

class CoverageContext extends BaseContext
{
    static private $_coverage;
    static private $_coverage_params;

    public function __construct($coverage_path, $whitelist, $blacklist)
    {
        // Constructeur pour avoir des paramètres,
        // sauf qu'on récupère les paramètres en BeforeSuite
        // donc on ne fait rien ici
    }

    /**
     * @BeforeSuite
     */
    public static function setUpCoverageParams(BeforeSuiteScope $scope)
    {
        $environment            = $scope->getEnvironment();
        $contexts_params        = $environment->getContextClassesWithArguments();
        self::$_coverage_params = $contexts_params['ETNA\FeatureContext\CoverageContext'];
    }

    /**
     * @BeforeSuite
     */
    public static function codeCoverageStart()
    {
        if (false === isset(self::$_coverage_params['coverage_path'])) {
            throw new \Exception("Missing parameter coverage_path");
        }

        $coverage_path = getcwd() . '/' . self::$_coverage_params['coverage_path'];
        if (false === file_exists($coverage_path)) {
            mkdir($coverage_path, 0777, true);
        }

        $filter = new PHP_CodeCoverage_Filter();
        if (true === isset(self::$_coverage_params['blacklist']) && true === is_array(self::$_coverage_params['blacklist'])) {
            foreach (self::$_coverage_params['blacklist'] as $blackElem) {
                $filter->addDirectoryToBlacklist(getcwd() . "/{$blackElem}");
            }
        }

        if (true === isset(self::$_coverage_params['whitelist']) && true === is_array(self::$_coverage_params['whitelist'])) {
            foreach (self::$_coverage_params['whitelist'] as $whiteElem) {
                $filter->addDirectoryToWhitelist(getcwd() . "/{$whiteElem}");
            }
        }

        self::$_coverage = new PHP_CodeCoverage(null, $filter);
        self::$_coverage->start('Behat Test');
    }

    /**
     * @AfterSuite
     */
    public static function codeCoverageStop()
    {
        self::$_coverage->stop();

        $coverage_path = getcwd() . '/' . self::$_coverage_params['coverage_path'];

        $writer = new PHP_CodeCoverage_Report_PHP;
        $writer->process(self::$_coverage, "{$coverage_path}.php");

        $writer = new PHP_CodeCoverage_Report_HTML;
        $writer->process(self::$_coverage, $coverage_path);

        $writer = new PHP_CodeCoverage_Report_Clover();
        $writer->process(self::$_coverage, "{$coverage_path}.clover.xml");

        exec("open {$coverage_path}/index.html");
    }
}
