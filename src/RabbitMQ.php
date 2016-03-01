<?php

namespace ETNA\FeatureContext;

use GuzzleHttp\Client;

trait RabbitMQ
{
    static private function getRabbitMqClient()
    {
        return new Client([
            "base_uri" => "http://127.0.0.1:15672",
            "headers" => ["Content-Type" => "application/json"],
            "auth"    => ["guest", "guest"]
        ]);
    }

    /**
     * @BeforeSuite
     */
    static public function createVhosts()
    {
        $client = self::getRabbitMqClient();

        foreach (self::$vhosts as $vhost) {
            $vhost = str_replace('/', '%2f', $vhost);

            $client->put("/api/vhosts/{$vhost}");

            $client->put(
                "/api/permissions/{$vhost}/guest",
                [
                    "json" => [
                        "configure" => ".*",
                        "write"     => ".*",
                        "read"      => ".*",
                    ]
                ]
            );
        }
    }

    /**
     * @AfterSuite
     */
    static public function deleteVhosts()
    {
        $client = self::getRabbitMqClient();

        foreach (self::$vhosts as $vhost) {
            $vhost = str_replace('/', '%2f', $vhost);
            $client->delete("/api/vhosts/{$vhost}");
        }
    }
}
