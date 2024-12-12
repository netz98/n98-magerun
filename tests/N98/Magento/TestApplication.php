<?php

declare(strict_types=1);

namespace N98\Magento;

use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\MockObject\MockObject;
use Varien_Autoload;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Magento test-application, the one used in unit and integration testing.
 *
 * @package N98\Magento
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
class TestApplication
{
    private ?Application $application;

    private ?string $root;

    private ?string $varName;

    private ?string $baseName;

    private TestCase $testCase;

    /**
     * @param string $varName name of the environment variable containing the test-root
     * @param string $baseName name of the stop file containing the test-root
     */
    public static function getTestMagentoRootFromEnvironment(string $varName, string $baseName): ?string
    {
        $root = getenv($varName);
        if (empty($root) && strlen($baseName)) {
            $stopFile = getcwd() . '/' . $baseName;
            if (is_readable($stopFile) && $buffer = rtrim(file_get_contents($stopFile))) {
                $root = $buffer;
            }
        }

        if (empty($root)) {
            return null;
        }

        # directory test
        if (!is_dir($root)) {
            throw new RuntimeException(
                sprintf("%s path '%s' is not a directory (cwd: '%s', stopfile: '%s')", $varName, $root, getcwd(), $stopFile ?? ''),
            );
        }

        # resolve root to realpath to be independent to current working directory
        $rootRealpath = realpath($root);
        if (false === $rootRealpath) {
            throw new RuntimeException(
                sprintf("Failed to resolve %s path '%s' with realpath()", $varName, $root),
            );
        }

        return $rootRealpath;
    }

    public static function getConfig(TestCase $testCase): array
    {
        $testApplication = new TestApplication($testCase);
        return $testApplication->getApplication()->getConfig();
    }

    /**
     * TestApplication constructor.
     *
     * @param null $varname [optional] name of the environment variable containing the path to magento-root, "N98_MAGERUN_TEST_MAGENTO_ROOT" by default
     * @param null $basename [optional] of the stop-file, ".n98-magerun" by default
     */
    public function __construct(TestCase $testCase, $varname = null, $basename = null)
    {
        if (null === $varname) {
            $varname = 'N98_MAGERUN_TEST_MAGENTO_ROOT';
        }

        if (null === $basename) {
            $basename = '.n98-magerun';
        }

        $this->testCase = $testCase;
        $this->varName = $varname;
        $this->baseName = $basename;
    }

    /**
     * getter for the magento root directory of the test-suite
     *
     * @see ApplicationTest::testExecute
     *
     * @return string
     */
    public function getTestMagentoRoot(): ?string
    {
        if ($this->root) {
            return $this->root;
        }

        $varName = $this->varName;
        $root = self::getTestMagentoRootFromEnvironment($varName, $this->baseName);

        if (null === $root) {
            throw new SkippedTestError(
                sprintf('Please specify environment variable %s with path to your test magento installation!', $varName),
            );
        }

        return $this->root = $root;
    }

    /**
     * @return Application|MockObject
     */
    public function getApplication()
    {
        if ($this->application === null) {
            $root = $this->getTestMagentoRoot();

            /** @var Application|MockObject $application */
            $application = $this->testCase->getMockBuilder(Application::class)
                ->setMethods(['getMagentoRootFolder'])
                ->getMock();

            // Get the composer bootstrap
            if (defined('PHPUNIT_COMPOSER_INSTALL')) {
                $loader = require PHPUNIT_COMPOSER_INSTALL;
            } elseif (is_file(__DIR__ . '/../../../../../autoload.php')) {
                // Installed via composer, already in vendor
                $loader = require __DIR__ . '/../../../../../autoload.php';
            } else {
                // Check if testing root package without PHPUnit
                $loader = require __DIR__ . '/../../../vendor/autoload.php';
            }

            $application->setAutoloader($loader);
            $application->method('getMagentoRootFolder')->willReturn($root);

            spl_autoload_unregister([Varien_Autoload::instance(), 'autoload']);

            $application->init();
            $application->initMagento();

            spl_autoload_unregister([Varien_Autoload::instance(), 'autoload']);

            $this->application = $application;
        }

        return $this->application;
    }
}
