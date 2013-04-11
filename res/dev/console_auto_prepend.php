<?php

function includeIfExists($file)
{
    if (file_exists($file)) {
        return include $file;
    }
}

if ((!$loader = includeIfExists(__DIR__ . '/../../vendor/autoload.php'))
    && (!$loader = includeIfExists(__DIR__.'/../../../autoload.php'))
) {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

$application = new N98\Magento\Application($loader);
$application->detectMagento();
if ($application->initMagento()) {
    echo <<<WELCOME
===========================
MAGENTO INTERACTIVE CONSOLE
===========================
WELCOME;
    echo PHP_EOL . PHP_EOL . 'Initialized Magento (' . \Mage::getVersion() . ')' . PHP_EOL . PHP_EOL;
} else {
    echo "FATAL: Magento could not be initialized." . PHP_EOL;
}
