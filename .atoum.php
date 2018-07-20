<?php

use mageekguy\atoum\writers\std;

// Add tests from each local bundles
$runner->addTestsFromDirectory(__DIR__.'/src');

// rapport html de couverture
$coverage = new atoum\report\fields\runner\coverage\html(
    'Code coverage',
    $path = __DIR__.'/public/coverage'
);
$coverage->setRootUrl('http://127.0.0.1:8000/coverage/index.html');
$coverage->addSrcDirectory(
    __DIR__ . '/src',
    function($file) {
        if($file->isDir()) {
            return true;
        }

        if($file->getExtension() === 'php') {
            return true;
        }

        return false;
    }
);

// notif ordi dev
$images = __DIR__.'/vendor/atoum/atoum/resources/images/logo';
$notifier = new \mageekguy\atoum\report\fields\runner\result\notifier\image\libnotify();
$notifier
    ->setSuccessImage($images . DIRECTORY_SEPARATOR . 'success.png')
    ->setFailureImage($images . DIRECTORY_SEPARATOR . 'failure.png')
;

// Configure code coverage scope
$script->excludeDirectoriesFromCoverage([__DIR__.'/vendor']);

// lancement des notif et cc en fin de tests
$report = $script->AddDefaultReport();
$report
    ->addField($coverage, array(atoum\runner::runStop))
    ->addField($notifier, array(atoum\runner::runStop))
;