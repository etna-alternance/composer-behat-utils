<?php

namespace ETNA\FeatureContext;

// TODO Utiliser un provider Guzzle pour remplacer les curl

trait RabbitMQ {
    /**
     * @BeforeSuite
     */
    static public function createVhosts()
    {
        foreach (self::$vhosts as $vhost) {
            $vhost = str_replace('/', '%2f', $vhost);
            passthru("curl -i -s -u guest:guest -H \"content-type:application/json\" -o /dev/null -XPUT http://127.0.0.1:15672/api/vhosts/{$vhost}");
            passthru("curl -i -s -u guest:guest -H \"content-type:application/json\" -o /dev/null -XPUT http://127.0.0.1:15672/api/permissions/{$vhost}/guest -d '{ \"configure\":\".*\", \"write\":\".*\", \"read\":\".*\" }'");
        }
    }

    /**
     * @AfterSuite
     */
    static public function deleteVhosts()
    {
        foreach (self::$vhosts as $vhost) {
            $vhost = str_replace('/', '%2f', $vhost);
            passthru("curl -i -s -u guest:guest -H \"content-type:application/json\" -o /dev/null -XDELETE http://127.0.0.1:15672/api/vhosts/{$vhost}");
        }
    }
}
