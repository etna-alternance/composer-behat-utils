<?php

namespace ETNA\FeatureContext;

trait ElasticSearchLock
{
    /**
     * @BeforeSuite
     */
    static public function lockElasticSearch()
    {
        self::lockOrUnlockElasticSearch("lock");
    }

    /**
     * @AfterSuite
     */
    public static function unlockElasticSearch()
    {
        self::lockOrUnlockElasticSearch("unlock");
    }

    /**
     * Bloque ou débloque les écritures sur l'elasticsearch
     *
     * @param string $action "lock" ou "unlock" pour faire l'action qui porte le même nom
     */
    private static function lockOrUnlockElasticSearch($action)
    {
        switch (true) {
            case false === isset(self::$silex_app):
            case false === isset(self::$silex_app["elasticsearch.server"]):
            case false === isset(self::$silex_app["elasticsearch.index"]):
                throw new \Exception(__METHOD__ . "::{$action}: Missing parameter");
        }

        $action = ($action === "lock") ? "true" : "false";

        $server = self::$silex_app["elasticsearch.server"] . self::$silex_app["elasticsearch.index"];
        exec(
            "curl -XPUT '" . $server . "/_settings' -d '
            {
                \"index\" : {
                    \"blocks.read_only\" : {$action}
                }
            }
            ' 2> /dev/null"
        );
    }
}
