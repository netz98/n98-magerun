<?php

if (!class_exists('N98_Magerun_Bootstrap')) {
    class N98_Magerun_Bootstrap
    {
        public static function includeIfExists($file)
        {
            if (file_exists($file)) {
                return include $file;
            }
        }

        /**
         * @throws ErrorException
         * @return \Composer\Autoload\ClassLoader
         */
        public static function getLoader()
        {
            if ((!$loader = \N98_Magerun_Bootstrap::includeIfExists(__DIR__ . '/../vendor/autoload.php'))
                && (!$loader = \N98_Magerun_Bootstrap::includeIfExists(__DIR__ . '/../../../autoload.php'))) {
                throw new ErrorException('You must set up the project dependencies, run the following commands:' . PHP_EOL .
                    'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
                    'php composer.phar install' . PHP_EOL);
            }

            return $loader;
        }
    }
}

try {
    $loader = \N98_Magerun_Bootstrap::getLoader();
    $application = new \N98\Magento\Application($loader);

    return $application;

} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
