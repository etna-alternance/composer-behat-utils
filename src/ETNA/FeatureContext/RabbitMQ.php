<?php

namespace ETNA\FeatureContext;

trait RabbitMQ {
    /**
     * @BeforeSuite
     */
    static public function createVhosts()
    {
        foreach (self::$vhosts as $vhost) {
            passthru("rabbitmqctl add_vhost {$vhost} > /dev/null");
            passthru("rabbitmqctl set_permissions -p {$vhost} guest '.*' '.*' '.*' > /dev/null");
        }
    }

    /**
     * @AfterSuite
     */
    static public function deleteVhosts()
    {
        foreach (self::$vhosts as $vhost) {
            passthru("rabbitmqctl delete_vhost {$vhost} > /dev/null");
        }
    }
}
