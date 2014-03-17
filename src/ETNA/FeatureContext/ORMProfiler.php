<?php

namespace ETNA\FeatureContext;

trait ORMProfiler
{
    static private $currentQuery =  0;
    static private $max_queries  = 10;

    /**
     * @BeforeScenario
     */
    public function resetProfiler()
    {
        self::$currentQuery += self::$silex_app["orm.profiler"]->currentQuery;

        self::$max_queries = 10;

        self::$silex_app["orm.profiler"]->queries      = [];
        self::$silex_app["orm.profiler"]->currentQuery = 0;
    }

    /**
     * @AfterSuite
     */
    public static function showQueryCount()
    {
        echo "\n# total queries : ", self::$currentQuery, "\n";
    }

    /**
     * @Given /^que j\'ai le droit de faire (\d+) requetes SQL$/
     */
    public function queJAiLeDroitDeFaireRequetesSql($nb)
    {
        self::$max_queries = $nb;
    }
}
