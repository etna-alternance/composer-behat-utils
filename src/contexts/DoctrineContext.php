<?php

use Behat\Behat\Tester\Exception\PendingException;

class DoctrineContext extends BaseContext
{
    static private $query_count = 0;
    static private $max_queries = 10;

    public function __construct($max_queries)
    {
        self::$max_queries = $max_queries;
    }

    /**
     * @BeforeSuite
     */
    public static function dump()
    {
        passthru("vendor/doctrine/orm/bin/doctrine orm:schema-tool:drop --force --full-database");
        passthru("./bin/dump ./Tests/Data/test*.sql");
    }

    public function checkMaxQueries($method, $response)
    {
        if (in_array($method, ['GET', 'PUT', 'DELETE']) && $response["headers"]["x-orm-profiler-count"] >= self::$max_queries) {
            $queries = [];
            foreach (json_decode($response["headers"]["x-orm-profiler-queries"]) as $query) {
                $queries[md5($query->sql)]["sql"]      = $query->sql;
                $queries[md5($query->sql)]["params"][] = $query->params;
            }

            throw new PendingException("Too many SQL queries ({$response["headers"]["x-orm-profiler-count"]})");
        }
    }

    /**
     * @BeforeScenario
     */
    public function resetProfiler()
    {
        self::$query_count += self::$silex_app["orm.profiler"]->currentQuery;

        self::$silex_app["orm.profiler"]->queries      = [];
        self::$silex_app["orm.profiler"]->currentQuery = 0;

        self::$max_queries = $this->getParameter("max_queries");
    }

    /**
     * @AfterSuite
     */
    public static function showQueryCount()
    {
        echo "\n# total queries : ", self::$query_count, "\n";
    }

    /**
     * @Given /^que j\'ai le droit de faire (\d+) requetes SQL$/
     */
    public function queJaiLeDroitDeFaireRequetesSql($nb)
    {
        self::$max_queries = $nb;
    }

    /**
     * @BeforeScenario
     */
    public function beginTransaction()
    {
        self::$silex_app["db"]->beginTransaction();
        self::$silex_app["orm.em"]->clear();
    }

    /**
     * @AfterScenario
     */
    public function rollback()
    {
        self::$silex_app["db"]->rollback();
    }
}
