**************
Test Framework
**************

We deliver a test framework for n98-magerun commands.

=============
Configuration
=============

Set the environment variable `N98_MAGERUN_TEST_MAGENTO_ROOT` with a path to a magento installation
which can be used to run tests.

i.e.

export N98_MAGERUN_TEST_MAGENTO_ROOT=/home/myinstallation

=========
Run Tests
=========

You need PHPUnit to run the tests.
If you don't have PHPUnit installed on your system you can use the following command to install all test tools
at once.

..code-block:: sh

   composer.phar --dev install

Run PHPUnit in n98-magerun root folder.
If you have installed with composer you can run::

   vendor/bin/phpunit
