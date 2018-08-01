<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;

class ElasticContext extends BaseContext
{
    /**
     * @BeforeSuite
     */
    public static function indexElasticSearch()
    {
        passthru("php bin/console elasticsearch:index --reset");
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
