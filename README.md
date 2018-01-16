composer-behat-coverage
=======================

[![Dependency Status](https://www.versioneye.com/user/projects/53dde6fe8e78abc19100006d/badge.svg)](https://www.versioneye.com/user/projects/53dde6fe8e78abc19100006d)

Composer for php code coverage with behat

## Pour utiliser les tests mocks sur proxy
-----------------------

- Ajouter le context des mock dans le behat.yml avec ses paramètres :
```
suites:
        default:
            paths:
                - %paths.base%/Tests/Functional/features
            contexts:
                - ETNA\FeatureContext\HttpApiMockContext:
                    host: '0.0.0.0'
                    port: '8080'
```

- Penser à rajouter l'URL du proxy dans l'env de testing sur
l'adresse et le port du serveur phiremock:
exemple:
```
putenv("CHANGEREQUEST_API_URL=http://localhost:8080");
```

- Lancer le serveur phiremock avant de lancer les tests:
```
./vendor/bin/phiremock -p 8080 -i 0.0.0.0 -d
```


## Dependencies
-----------------------
In your composer.json :
```
"require-dev": {
    "behat/behat": "3.x@stable",
    "phpunit/php-code-coverage": "2.0.*@dev",
    "phpunit/phpcov": "2.0.*@dev",
},
```

## Install
-----------------------
 * use this behat.yml if you dont have one :
```
# behat.yml
default:
    autoload:
        '': %paths.base%/path/to/contexts
    suites:
        default:
            paths:
                - %paths.base%/Tests/Functional/features
            contexts:
                - ETNA\FeatureContext\MainContext
                - ETNA\FeatureContext\ApiContext
                - ETNA\FeatureContext\DoctrineContext:
                    max_queries: 10
                - ETNA\FeatureContext\ElasticContext
                - ETNA\FeatureContext\FixedDateContext:
                    date: "2016-04-12 14:42:42"
                - ETNA\FeatureContext\AuthContext
                - ETNA\FeatureContext\TimeProfilerContext:
                    max_time: 200
    formatters:
        progress:
            decorated:           true
            verbose:             false
            time:                true
            language:            fr
            output_path:         null
            multiline_arguments: true
ci:
    suites:
        default:
            contexts:
                - ETNA\FeatureContext\MainContext
                - ETNA\FeatureContext\ApiContext
                - ETNA\FeatureContext\DoctrineContext:
                    max_queries: 10
                - ETNA\FeatureContext\ElasticContext
                - ETNA\FeatureContext\FixedDateContext:
                    date: "2016-04-12 14:42:42"
                - ETNA\FeatureContext\AuthContext
                - ETNA\FeatureContext\CoverageContext:
                    coverage_path: /tmp/behat/coverage
                    whitelist:
                        - app
                    blacklist:
                        - vendor
                        - bin
                        - tmp
                        - features
                        - Tests
    formatters:
        progress:
            output_path:         null
        junit:
            output_path: tmp/behat/behatJunit
        html:
            output_path: tmp/behat/behat_report.html
wip:
    suites:
        default:
            filters:
                tags: @wip
```

##Run
-----------------------

```
 $>./bin/behat -p ci
```

 * and view results like this in
```
 ./tmp/behat/
├── behatJunit
├── behat_report.html
└── coverage
    ├── config
    ├── controllers
    ├── css
    ├── fonts
    ├── index.html
    ├── index.php.html
    ├── js
    ├── models
    ├── modelsStats
    ├── repositories
    └── repositoriesStats
```
