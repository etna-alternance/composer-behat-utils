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
        $app = include getcwd() . "/public/index.php";

        echo "Creating indexes\n";
        foreach ($app["elasticsearch.names"] as $name) {
            $app['elasticsearch.create_index']($name, true);
            $app["elasticsearch.{$name}.reindex"]();
        }
    }

    /**
     * @BeforeScenario
     */
    public function lockElasticSearch()
    {
        foreach (self::$silex_app["elasticsearch.names"] as $name) {
            self::lockOrUnlockElasticSearch($name, "lock");
        }
    }

    /**
     * @AfterScenario
     */
    public function unlockElasticSearch()
    {
        foreach (self::$silex_app["elasticsearch.names"] as $name) {
            self::lockOrUnlockElasticSearch($name, "unlock");
        }
    }

    private static function lockOrUnlockElasticSearch($name, $action)
    {
        $action = ("lock" === $action) ? "true" : "false";

        $server = self::$silex_app["elasticsearch.{$name}.server"] . self::$silex_app["elasticsearch.{$name}.index"];
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
