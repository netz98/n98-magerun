<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\TestCase;

class GetCommandTest extends TestCase
{
    /**
     * @test
     */
    public function nullValues()
    {
        # Very old Magento versions do not support NULL values in configuration values
        $this->skipMagentoMinimumVersion('1.6.2.0', '1.11.2.0');

        $this->assertDisplayRegExp(
            array(
                'command'   => 'config:set',
                '--no-null' => null,
                'path'      => 'n98_magerun/foo/bar',
                'value'     => 'NULL',
            ),
            '~^n98_magerun/foo/bar => NULL$~'
        );

        $this->assertDisplayContains(
            array(
                'command'          => 'config:get',
                '--magerun-script' => null,
                'path'             => 'n98_magerun/foo/bar',
            ),
            'config:set --no-null --scope-id=0 --scope=default'
        );

        $this->assertDisplayContains(
            array(
                'command' => 'config:set',
                'path'    => 'n98_magerun/foo/bar',
                'value'   => 'NULL',
            ),
            'n98_magerun/foo/bar => NULL (NULL/"unknown" value)'
        );

        $this->assertDisplayContains(
            array(
                'command' => 'config:get',
                'path'    => 'n98_magerun/foo/bar',
            ),
            '| n98_magerun/foo/bar | default | 0        | NULL (NULL/"unknown" value) |'
        );

        $this->assertDisplayContains(
            array(
                'command'          => 'config:get',
                '--magerun-script' => true, # needed to not use the previous output cache
                'path'             => 'n98_magerun/foo/bar',
            ),
            'config:set --scope-id=0 --scope=default -- \'n98_magerun/foo/bar\' NULL'
        );
    }

    public function provideFormatsWithNull()
    {
        return array(
            array(null, '~\\Q| n98_magerun/foo/bar | default | 0        | NULL (NULL/"unknown" value) |\\E~'),
            array('csv', '~\\Qn98_magerun/foo/bar,default,0,NULL\\E~'),
            array('json', '~"Value": *null~'),
            array('xml', '~\\Q<Value>NULL</Value>\\E~'),
        );
    }

    /**
     * @test
     * @dataProvider provideFormatsWithNull
     */
    public function nullWithFormat($format, $expected)
    {
        # Very old Magento versions do not support NULL values in configuration values
        $this->skipMagentoMinimumVersion('1.6.2.0', '1.11.2.0');

        $this->assertDisplayContains(
            array(
                'command' => 'config:set',
                'path'    => 'n98_magerun/foo/bar',
                'value'   => 'NULL',
            ),
            'n98_magerun/foo/bar => NULL (NULL/"unknown" value)'
        );

        $this->assertDisplayRegExp(
            array(
                'command'  => 'config:get',
                '--format' => $format,
                'path'     => 'n98_magerun/foo/bar',
            ),
            $expected
        );
    }

    public function testExecute()
    {
        /**
         * Add a new entry (to test for it)
         */
        $this->assertDisplayContains(
            array(
                'command' => 'config:set',
                'path'    => 'n98_magerun/foo/bar',
                'value'   => '1234',
            ),
            'n98_magerun/foo/bar => 1234'
        );

        $this->assertDisplayContains(
            array(
                'command' => 'config:get',
                'path'    => 'n98_magerun/foo/bar',
            ),
            '| n98_magerun/foo/bar | default | 0        | 1234  |'
        );

        $this->assertDisplayContains(
            array(
                'command'         => 'config:get',
                'path'            => 'n98_magerun/foo/bar',
                '--update-script' => true,
            ),
            "\$installer->setConfigData('n98_magerun/foo/bar', '1234');"
        );

        $this->assertDisplayContains(
            array(
                'command'          => 'config:get',
                'path'             => 'n98_magerun/foo/bar',
                '--magerun-script' => true,
            ),
            "config:set --scope-id=0 --scope=default -- 'n98_magerun/foo/bar' '1234'"
        );

        /**
         * Dump CSV
         */
        $input = array(
            'command'  => 'config:get',
            'path'     => 'n98_magerun/foo/bar',
            '--format' => 'csv',
        );
        $this->assertDisplayContains($input, 'Path,Scope,Scope-ID,Value');
        $this->assertDisplayContains($input, 'n98_magerun/foo/bar,default,0,1234');

        /**
         * Dump XML
         */
        $input = array(
            'command'  => 'config:get',
            'path'     => 'n98_magerun/foo/bar',
            '--format' => 'xml',
        );
        $this->assertDisplayContains($input, '<table>');
        $this->assertDisplayContains($input, '<Value>1234</Value>');

        /**
         * Dump JSON
         */
        $this->assertDisplayRegExp(
            array(
                'command'  => 'config:get',
                'path'     => 'n98_magerun/foo/bar',
                '--format' => 'json',
            ),
            '/"Value":\s*"1234"/'
        );
    }

    /**
     * Helper method to skip test if a minimum Magento (1) version
     * is not given (Community and Enterprise edition only)
     *
     * @param string $community version (e.g. "1.6.2.0")
     * @param string $enterprise version (e.g. "1.11.2.0")
     */
    private function skipMagentoMinimumVersion($community, $enterprise)
    {
        $this->getApplication()->initMagento();
        $magentoVersion = \Mage::getVersion();
        if (is_callable(array('Mage', 'getEdition'))) {
            $magentoEdition = \Mage::getEdition();
        } else {
            $magentoEdition =
                version_compare($magentoVersion, '1.10', '<')
                ? 'Community'
                : 'Enterprise';
        }

        switch ($magentoEdition) {
            case 'Community':
                if (version_compare($magentoVersion, $community, '<')) {
                    $this->markTestSkipped(
                        sprintf(
                            'Test requires minimum Magento version of "%s", version "%s" is in use',
                            $community,
                            $magentoVersion
                        )
                    );
                }
                break;
            case 'Enterprise':
                if (version_compare($magentoVersion, $enterprise, '<')) {
                    $this->markTestSkipped(
                        sprintf(
                            'Test requires minimum Magento version of "%s", version "%s" is in use',
                            $enterprise,
                            $magentoVersion
                        )
                    );
                }
                break;
            default:
                $this->markTestSkipped(
                    sprintf(
                        'Test requires community or enterprise edition, Magento edition "%s" given',
                        $magentoEdition
                    )
                );
        }
    }
}
