<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Context\Context;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Contexte behat permettant de tester des configurations de bundle en bootant des kernels temporaires.
 */
class SymfonyTestKernelContext extends BaseContext
{
    /** @var Kernel Le kernel de test pour tester des configurations alternatives */
    private $test_kernel;

    /** @var array<string> Liste de bundles à register dans le kernel de test */
    private $bundles;

    /**
     * Constructeur de la classe
     *
     * @param array<string> $bundles Liste des bundles supplémentaires à charger
     */
    public function __construct($bundles = [])
    {
        $this->test_kernel = null;
        $this->bundles     = $bundles;
    }

    /**
     * Crée un nouveau kernel
     *
     * @Given je crée un nouveau kernel de test
     */
    public function jeCreeUnNouveauKernelDeTest(): void
    {
        $this->test_kernel = new class('test', true) extends Kernel {
            use MicroKernelTrait;

            public static $config_path;
            public static $additional_bundles;

            public function getCacheDir()
            {
                return $this->getProjectDir().'/tmp/cache/behat-env';
            }

            public function registerBundles()
            {
                $bundles = array_merge(self::$additional_bundles, [
                    \Symfony\Bundle\FrameworkBundle\FrameworkBundle::class,
                ]);

                foreach ($bundles as $bundle) {
                    yield new $bundle();
                }
            }

            protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader) {
                $loader->load(self::$config_path);
            }

            protected function configureRoutes(RouteCollectionBuilder $routes) {
                return;
            }

            public function shutdown() {
                // Je suis obligé de faire ca pour pouvoir tester plusieurs configurations dans la même suite
                // Merci Symfony d'être aussi flexible sur le cache :)
                exec("rm -rf {$this->getCacheDir()}");
                parent::shutdown();
            }
        };
        $this->test_kernel::$additional_bundles = $this->bundles;
    }

    /**
     * Sette le fichier de config chargé par le kernel de test
     *
     * @Given /^je configure le kernel avec le fichier "([^"]*)"$/
     */
    public function jeConfigureLeKernelAvecLeFichier($config_file)
    {
        $this->test_kernel::$config_path = $this->requests_path . $config_file;
    }

    /**
     * Boot le kernel et on récupère l'éventuelle exception
     *
     * @Given je boot le kernel
     */
    public function jeBootLeKernel()
    {
        $test_kernel = $this->test_kernel;

        $this->getContext("ETNA\FeatureContext\ExceptionContainerContext")->try(
            function () use ($test_kernel) {
                $this->test_kernel->boot();
            }
        );
    }

    /**
     * Shutdown le kernel
     *
     * @Given je n'ai plus besoin du kernel de test
     */
    public function jeNAiPlusBesoinDuKernelDeTest()
    {
        if (null === $this->test_kernel) {
            throw new \Exception("No test kernel to remove");
        }
        $this->test_kernel->shutdown();
        $this->test_kernel = null;
    }

    /**
     * Compare les paramêtres de l'application avec ceux donnés en paramêtre
     *
     * @param string $expected_params Liste des paramêtres attendus dans le kernel
     *
     * @Given /^les paramêtres de mon application devraient être :$/
     */
    public function lesParametresDeMonApplicationDevraientEtre($expected_params)
    {
        $expected = json_decode($expected_params);

        if (null === $expected) {
            throw new \Exception("json_decode error");
        }
        if (null === $this->test_kernel) {
            throw new \Exception("No test_kernel to check params on");
        }

        $actual_params = new \stdClass();
        foreach ($expected as $param_key => $param_value) {
            $actual_params->$param_key = $this->test_kernel->getContainer()->getParameter($param_key);
        }

        $this->check($expected, $actual_params, "result", $errors);
        $this->handleErrors($actual_params, $errors);
    }

    /**
     * Vérifie la présence du service donné en paramêtre
     *
     * @param string $service_name Le nom du service dont on vérifie l'existance
     *
     * @Given /^le service "([^"]*)" devrait exister$/
     */
    public function leServiceDevraitExister($service_name)
    {
        if (!$this->test_kernel->getContainer()->has($service_name)) {
            throw new \Exception("{$service_name} doesn't exists");
        }
    }

    /**
     * Force l'instanciation d'un service en faisant appel à ce dernier
     *
     * @param string $service_name Le nom du service à instancier
     *
     * @Given /^je force l'instanciation du service "([^"]*)"$/
     */
    public function jeForceLInstanciationDuService($service_name)
    {
        $test_kernel = $this->test_kernel;

        if ($test_kernel->getContainer()->has($service_name)) {
            $this->getContext("ETNA\FeatureContext\ExceptionContainerContext")->try(
                function () use ($test_kernel, $service_name) {
                    $test_kernel->getContainer()->get($service_name);
                }
            );
        } else {
            throw new \Exception("{$service_name} doesn't exists");
        }
    }
}
