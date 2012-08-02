========================
netz98 magerun CLI tools
========================

The n98 magerun cli tools provides some handy tools to work with magento from command line.

Compatibility
-------------
The tools are currently only tested with PHP 5.3.10 within Ubuntu 12.04 Linux.
If you are a windows user you can help us with a quick test.

The tool does currently not work with Magento 2.x branch.

Installation
------------

There are two ways to install the tools:

Download phar file
""""""""""""""""""

::

    wget https://github.com/netz98/n98-magerun/raw/master/n98-magerun.phar

You can make the .phar file executable.

::

    chmod +x ./n98-magerun.phar

If you wan't to use command system wide you can copy it to `/usr/local/bin`.

::

    sudo cp ./n98-magerun.phar /usr/local/bin/`

From source with composer
"""""""""""""""""""""""""

#. Clone git repository::

    git clone https://github.com/netz98/n98-magerun

#. Download composer::

    curl -s https://getcomposer.org/installer | php

#. Let composer do all the work for you::

    php ./composer.phar install

Usage / Commands
----------------

All commands try to detect the current Magento root directory.
If you have multiple magento installation you must change your working directory to
the preferred installation.

Print database informations
"""""""""""""""""""""""""""

::

    n98-magerun.phar database:info

Dump database
"""""""""""""""""""""""""""

Direct dump with mysqldump::

    n98-magerun.phar database:dump

Only the mysqldump command::

    n98-magerun.phar database:dump --only-command

Generate local.xml file
"""""""""""""""""""""""

::

    n98-magerun.phar local-config:generate

Dump global xml config
""""""""""""""""""""""

::

    n98-magerun.phar config:dump > extern_file.xml

List magento cache status
"""""""""""""""""""""""""

::

    n98-magerun.phar cache:list

Clear magento cache
"""""""""""""""""""

::

    n98-magerun.phar cache:clear

Or only one cache type like i.e. full_page cache::

   n98-magerun.phar cache:clear full_page

List magento caches
"""""""""""""""""""

::

    n98-magerun.phar cache:list

Disable magento cache
"""""""""""""""""""""

::

    n98-magerun.phar cache:disable

Enable magento cache
""""""""""""""""""""

::

    n98-magerun.phar cache:enable

List admin users
""""""""""""""""

::

    n98-magerun.phar admin:user:list

Change admin user password
""""""""""""""""""""""""""

::

    n98-magerun.phar admin:user:change-password

Bash autocompletion
===================

Copy the file **bash_complete** as **n98-magerun.phar** in your bash autocomplete folder.
In my ubuntu system this can be done with the following command::

    cp bash_complete /etc/bash_completion.d/n98-magerun.phar
