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
        if (!isset(self::$silex_app) || !isset(self::$silex_app["elasticsearch.server"]) || !isset(self::$silex_app["elasticsearch.index"])) {
            die("ElasticSearch Lock : l." . (__LINE__ - 2));
        }
        $server = self::$silex_app["elasticsearch.server"] . self::$silex_app["elasticsearch.index"];
        exec(
            "curl -XPUT '" . $server . "/_settings' -d '
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
        if (!isset(self::$silex_app) || !isset(self::$silex_app["elasticsearch.server"]) || !isset(self::$silex_app["elasticsearch.index"])) {
            die("ElasticSearch Lock : l." . (__LINE__ - 2));
        }
        $server = self::$silex_app["elasticsearch.server"] . self::$silex_app["elasticsearch.index"];
        exec(
            "curl -XPUT '" . $server . "/_settings' -d '
            {
                \"index\" : {
                    \"blocks.read_only\" : false
                }
            }
            ' 2> /dev/null"
        );
    }
}
