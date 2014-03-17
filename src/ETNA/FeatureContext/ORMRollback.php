<?php

namespace ETNA\FeatureContext;

trait ORMRollback
{
    /**
     * @BeforeScenario
     */
    public function beginTransaction()
    {
        self::$silex_app["db"]->beginTransaction();
        self::$silex_app["orm.em"]->clear();
    }

    /**
     * @AfterScenario
     */
    public function rollback()
    {
        self::$silex_app["db"]->rollback();
    }
}
