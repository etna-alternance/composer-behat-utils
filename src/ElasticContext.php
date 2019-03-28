<?php
namespace ETNA\FeatureContext;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;


class ElasticContext extends BaseContext
{
    private static $indexed = false;

    /**
     * @BeforeScenario
     */
    public function indexElasticSearch()
    {
        if (false === self::$indexed) {
            $application = new Application($this->getKernel());
            $application->setAutoExit(false);
            $application->run(
                new ArrayInput(['command' => 'elasticsearch:index', '--reset' => true]),
                new NullOutput()
            );
            self::$indexed = true;
        }
    }

    /**
     * @BeforeScenario
     */
    public function lockElasticSearch()
    {
        $container = $this->getKernel()->getContainer();
        foreach ($container->getParameter("elasticsearch.names") as $name) {
            $container->get("elasticsearch.elasticsearch_service")->lock($name);
        }
    }

    /**
     * @AfterScenario
     */
    public function unlockElasticSearch()
    {
        $container = $this->getKernel()->getContainer();
        foreach ($container->getParameter("elasticsearch.names") as $name) {
            $container->get("elasticsearch.elasticsearch_service")->unlock($name);
        }
    }
}
