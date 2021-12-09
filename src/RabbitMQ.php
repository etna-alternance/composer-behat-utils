<?php

namespace ETNA\FeatureContext;

use GuzzleHttp\Client;

trait RabbitMQ
{
    static private function getRabbitMqClient()
    {
        $base_url = getenv("RABBITMQ_MGMT_URL") ?: "http://localhost:15672";
        $user = getenv("RABBITMQ_USER") ?: "guest";
        $password = getenv("RABBITMQ_PASS") ?: "guest"; 

        return new Client([
            "base_uri" => $base_url,
            "headers" => ["Content-Type" => "application/json"],
            "auth"    => [$user, $password]
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
