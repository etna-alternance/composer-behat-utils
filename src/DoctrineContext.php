<?php

namespace ETNA\FeatureContext;

use ETNA\FeatureContext\BaseContext;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
class DoctrineContext extends BaseContext
{
    static private $max_queries;
    static private $query_count = 0;
    static private $dumped      = false;

    public function __construct($max_queries)
    {
        self::$max_queries = $max_queries;
    }

    /**
     * @BeforeScenario
     * Dump la base de donnée avant le premier scénario après on ignore.
     */
    public function dump()
    {
        if (!self::$dumped) {
            //On get les params de Doctrine
            $params = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getParams();

            //On crée une console application pour lancer la commande
            $application = new Application($this->getKernel());
            $application->setAutoExit(false);
            $input = new ArrayInput([
                'command'    => 'test:dump',
                '-u'         => $params['user'],
                '--host'     => $params['host'],
                '--password' => $params['password'],
            ]);

            $application->run($input);
            self::$dumped = true;
        }
    }

    public function checkMaxQueries($response)
    {
        $actual_queries_count = $response["headers"]["x-orm-profiler-count"];
        self::$query_count   += $actual_queries_count;
        if ($actual_queries_count >= self::$max_queries) {
            $this->getContext("ETNA\FeatureContext\ExceptionContainerContext")
            ->setException(new \Exception("Too many SQL queries ({$response["headers"]["x-orm-profiler-count"]})"));
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
