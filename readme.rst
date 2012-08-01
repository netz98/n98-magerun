========================
netz98 magerun CLI tools
========================

The n98 magerun cli tools provides some handy tools to work with magento from command line.

-------------
Compatibility
-------------
The tools are currently only tested with PHP 5.3.10 within
Ubuntu 12.04 Linux.
If you are a windows user you can help us with a quick test.

------------
Installation
------------

There are two ways to install the tools:

~~~~~~~~~~~~~~~~~~~~
Composer from source
~~~~~~~~~~~~~~~~~~~~

1. Clone git repository

    `git clone https://github.com/netz98/n98-magerun`

2. Download composer.

    `curl -s https://getcomposer.org/installer | php`

3. Let's do composer all the work.

    `php ./composer.phar install`

~~~~~~~~~~~~~~~~~~
Download phar file
~~~~~~~~~~~~~~~~~~

    `curl -s https://github.com/netz98/n98-magerun/blob/master/n98-magerun.phar`

You can make the .phar file executable.

    `chmod +x ./n98-magerun.phar`

----------------
Usage / Commands
----------------

All commands try to detect the current Magento root directory.
If you have multiple magento installation you must change your working directory to
the preferred installation.

~~~~~~~~~~~~~~~~~~~~~~~~~~~
Print database informations
~~~~~~~~~~~~~~~~~~~~~~~~~~~

    `n98-magerun.phar database:info`

~~~~~~~~~~~~~~~~~~~~~~~~~~~
Dump database
~~~~~~~~~~~~~~~~~~~~~~~~~~~

    `n98-magerun.phar database:dump`

~~~~~~~~~~~~~~~~~~~~~~~
Generate local.xml file
~~~~~~~~~~~~~~~~~~~~~~~

    `n98-magerun.phar local-config:generate`