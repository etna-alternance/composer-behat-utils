<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;

trait ElasticSearchLock
{
    /**
     * @BeforeSuite
     */
    static public function ElasticLock(SuiteEvent $event)
    {
        if (isset(self::$silex_app)) {
            die("ElasticSearch Lock : l." . (__LINE__ - 2));
        }
        exec(
            "curl -XPUT 'localhost:9200/prospects.test/_settings' -d '
            {
                \"index\" : {
                    \"blocks.read_only\" : true
                }
            }
            ' 2> /dev/null"
        );
    }

    /**
     * @AfterSuite
     */
    public static function ElasticUnlock()
    {
        if (isset(self::$silex_app)) {
            die("ElasticSearch Lock : l." . (__LINE__ - 2));
        }
        exec(
            "curl -XPUT 'localhost:9200/prospects.test/_settings' -d '
            {
                \"index\" : {
                    \"blocks.read_only\" : false
                }
            }
            ' 2> /dev/null"
        );
    }
}
