<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\TestCase;

/**
 * Checks installer configuration to not put packages in the wrong order in config.yml
 *
 * @package N98\Magento\Command\Installer
 */
class InstallCommandPackageVersionTest extends TestCase
{
    /**
     * @test that versions given are in order (latest up) across the package definitions in config.yml
     */
    public function versionListing()
    {
        $application = $this->getApplication();
        $application->add(new InstallCommand());
        /** @var InstallCommand $command */
        $command = $this->getApplication()->find('install');

        $tester = new InstallCommandTester();
        $packages = $tester->getMagentoPackages($command);

        $this->assertOngoingPackageVersions($packages, 2, 5);
    }

    /**
     * helper assertion to verify that all packages with multiple versions are listet with the latest and greatest
     * version first.
     *
     * @param array $packages
     * @param int $namespacesMinimum minimum number of package namespace (e.g. CE and mirror), normally 2
     * @param int $nonVersionsMaximum maximum number of packages that will trigger an assertion
     */
    private function assertOngoingPackageVersions(array $packages, $namespacesMinimum, $nonVersionsMaximum)
    {
        $nonVersions = 0;
        $nonVersionsList = array();
        $nameStack = array();
        $nameConstraint = array();

        foreach ($packages as $package) {
            $this->assertArrayHasKey('name', $package);
            $this->assertArrayHasKey('version', $package);
            $name = $package['name'];
            $version = $package['version'];
            $nameAndVersion = "$name $version";

            $this->assertArrayNotHasKey(
                $name,
                $nameConstraint,
                sprintf('duplicate package "%s"', $name)
            );
            $nameConstraint[$name] = 1;

            if (!$this->isQuadripartiteVersionNumber($version)) {
                $nonVersionsList[] = $nameAndVersion;
                $nonVersions++;
                continue;
            }

            list($namespace, $nameVersion) = $this->splitName($name);
            if ($nameVersion === null || $nameVersion !== $version) {
                $nonVersionsList[] = $name;
                $nonVersions++;
                continue;
            }
            $this->assertSame($version, $nameVersion);

            if (isset($nameStack[$namespace])) {
                $comparison = version_compare($nameStack[$namespace], $version);
                $message = sprintf(
                    "Check order of versions for package \"$namespace\", higher comes first, but got %s before %s",
                    $nameStack[$namespace],
                    $version
                );
                $this->assertGreaterThan(0, $comparison, $message);
            }
            $nameStack[$namespace] = $nameVersion;
        }

        $this->assertGreaterThanOrEqual($namespacesMinimum, count($nameStack));
        $message = sprintf('Too many non-versions (%s)', implode(', ', $nonVersionsList));
        $this->assertLessThan($nonVersionsMaximum, $nonVersions, $message);
    }

    /**
     * @test that demo-data-packages actually exist
     */
    public function demoDataPackages()
    {
        $application = $this->getApplication();
        $application->add(new InstallCommand());
        /** @var InstallCommand $command */
        $command = $this->getApplication()->find('install');

        $tester = new InstallCommandTester();
        $packages = $tester->getMagentoPackages($command);
        $demoDataPackages = $tester->getSampleDataPackages($command);

        $this->assertSampleDataPackagesExist($packages, $demoDataPackages);
    }

    private function assertSampleDataPackagesExist(array $packages, array $demoDataPackages)
    {
        $map = array();
        foreach ($demoDataPackages as $index => $package) {
            $map[$package['name']] = $index;
        }

        foreach ($packages as $index => $package) {
            if (!isset($package['extra']['sample-data'])) {
                continue;
            }
            $name = $package['extra']['sample-data'];
            $message = sprintf('Invalid sample-data "%s" (undefined) in package "%s"', $name, $package['name']);
            $this->assertArrayHasKey($name, $map, $message);
        }
    }

    /**
     * @param string $name
     * @return array
     */
    private function splitName($name)
    {
        list($nameSuffix, $nameVersion) = preg_split('~-(?=[^-]+$)~', $name) + array(1 => null);

        return array($nameSuffix, $nameVersion);
    }

    /**
     * @param string $buffer
     * @return bool
     */
    private function isQuadripartiteVersionNumber($buffer)
    {
        if (!preg_match('~^\d+\.\d+\.\d+\.\d+$~', $buffer)) {
            return false;
        }

        $parts = explode('.', $buffer);
        foreach ($parts as $part) {
            if ($part !== (string) (int) $part) {
                return false;
            }
        }

        return true;
    }
}
