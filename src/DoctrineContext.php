<?php

namespace ETNA\FeatureContext;

use ETNA\FeatureContext\BaseContext;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
class FeatureContext extends BaseContext
{
    static private $max_queries;
    static private $query_count = 0;

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

    public function checkMaxQueries($response)
    {
        $actual_queries_count = $response["headers"]["x-orm-profiler-count"];
        self::$query_count   += $actual_queries_count;
        if ($actual_queries_count >= self::$max_queries) {
            $this->getContext("ETNA\FeatureContext\ExceptionContainerContext")
            ->setException(new PendingException("Too many SQL queries ({$response["headers"]["x-orm-profiler-count"]})"));
        }
    }

    /**
     * @Given /j\'ai le droit de faire (\d+) requetes SQL$/
     */
    public function jaiLeDroitDeFaireRequetesSql($nb)
    {
        self::$max_queries = $nb;
    }

    /**
     * @BeforeScenario
     */
    public function beginTransaction()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->beginTransaction();
        $em->clear();
    }

    /**
     * @AfterScenario
     */
    public function rollback()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->rollback();
    }
}
