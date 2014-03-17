composer-behat-coverage
=======================

Composer for php code coverage with behat

Dependencies
-----------------------
In your composer.json :
```
"require-dev": {
    "behat/behat": "2.x@stable",
    "gquemener/behat-analysis-extension": "~1.0",
    "phpunit/php-code-coverage": "2.0.*@dev",
    "phpunit/phpcov": "2.0.*@dev",
},
```

Install
-----------------------
 * Add to your FeatureContext.php file :
```
     use ETNA\FeatureContext\Coverage;
```
 * use this behat.yml if you dont have one :
```
# behat.yml
default:
    formatter:
        name:                       progress
        parameters:
            decorated:              true
            verbose:                false
            time:                   true
            language:               fr
            output_path:            null
            multiline_arguments:    true
    paths:
        features: features
        bootstrap: %behat.paths.features%/bootstrap
    extensions:
            Behat\AnalysisExtension\Extension: ~
wip:
    filters:
        tags:       "@wip"
    formatter:
        name:       progress
ci:
    formatter:
        name:       progress,junit,html
        parameters:
            output_path: null,tmp/behat/behatJunit,tmp/behat/behat_report.html

    context:
        parameters:
            # Whether or not to collect code coverage
            enableCodeCoverage: true

            # Path to store the generated code coverage report
            coveragePath: /tmp/behat/coverage

            # White list of directories to collect coverage about
            whitelist:
                - app
            # Black list of directories to not collect coverage about
            blacklist:
                - tmp
                - features
                - bin
```

Run
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
