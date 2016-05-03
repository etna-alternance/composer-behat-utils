<?php

namespace ETNA\FeatureContext;

use Symfony\Component\Filesystem\Filesystem;

class SvnContext extends BaseContext
{
    /**
     * @BeforeScenario @svn
     *
     * On crée un repo svn dans lequel on importe le contenu du dossier src/
     * Ce repo fait office de svn-profs pour les tests
     */
    public static function createSvnRepo()
    {
        $base_dir = self::$silex_app['application_path'] . "/Tests/Data";
        exec("svnadmin create {$base_dir}/origin");
        exec("svn import -m'import' {$base_dir}/src file://{$base_dir}/origin");
    }

    /**
     * @AfterScenario @svn
     */
    public static function deleteSvnRepos()
    {
        $base_dir    = self::$silex_app['application_path'] . "/Tests/Data";
        $file_system = new Filesystem();
        $file_system->remove("{$base_dir}/origin");
    }
}
