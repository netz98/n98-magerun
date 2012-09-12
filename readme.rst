========================
netz98 magerun CLI tools
========================

The n98 magerun cli tools provides some handy tools to work with Magento from command line.

Compatibility
-------------
The tools are currently only tested with PHP 5.3.10 within Ubuntu 12.04 Linux.
If you are a Windows user you can help us with a quick test.

The tools should work with Magento 2 development branch.

Installation
------------

There are two ways to install the tools:

Download phar file
""""""""""""""""""

.. code:: bash

    wget https://raw.github.com/netz98/n98-magerun/master/n98-magerun.phar

You can make the .phar file executable.

::

    chmod +x ./n98-magerun.phar

If you want to use command system wide you can copy it to `/usr/local/bin`.

::

    sudo cp ./n98-magerun.phar /usr/local/bin/

From source with composer
"""""""""""""""""""""""""

#. Clone git repository::

    git clone https://github.com/netz98/n98-magerun

#. Download composer::

    curl -s https://getcomposer.org/installer | php

#. Let composer do all the work for you::

    php ./composer.phar install

#. Run cli.php::

    php cli.php

It's recommended to install the .phar file system wide.

Update
------

Since version 1.1.0 we deliver a self-update script within the phar file::

    n98-magerun.phar self-update

If file was installed system wide do not forget "sudo".

Usage / Commands
----------------

All commands try to detect the current Magento root directory.
If you have multiple Magento installation you must change your working directory to
the preferred installation.

https://github.com/netz98/n98-magerun/wiki/Commands

You can list all available commands by::

   n98-magerun.phar list


If you don't have installed the .phar file system wide you can call it with the php cli interpreter::

   php n98-magerun.phar list

Print database information
"""""""""""""""""""""""""""

::

    n98-magerun.phar database:info

Dump database
"""""""""""""""""""""""""""

Direct dump with mysqldump::

    n98-magerun.phar database:dump

Only the mysqldump command::

    n98-magerun.phar database:dump --only-command

Database Console / MySQL Client
"""""""""""""""""""""""""""""""

Opens the MySQL console client with your database settings from local.xml

* Requires MySQL CLI tools

.. code-block:: bash

   n98-magerun.phar database:console

List Indexes
""""""""""""

.. code-block:: bash

   n98-magerun.phar index:list

Reindex a Index
"""""""""""""""

Index by indexer code. Code is optional. If you don't specify a code you can pick a indexer from a list.

.. code-block:: bash

   n98-magerun.phar index:reindex [code]

Reindex All
"""""""""""

Loops all magento indexes and triggers reindex.

.. code-block:: bash

   n98-magerun.phar index:reindex:all

Generate local.xml file
"""""""""""""""""""""""

::

    n98-magerun.phar local-config:generate

Dump global xml config
""""""""""""""""""""""

::

    n98-magerun.phar config:dump > extern_file.xml

List Magento cache status
"""""""""""""""""""""""""

::

    n98-magerun.phar cache:list

Clean Magento cache
"""""""""""""""""""

Cleans expired cache entries.
If you like to remove all entries use `cache:flush`

::

    n98-magerun.phar cache:clean

Or only one cache type like i.e. full_page cache::

   n98-magerun.phar cache:clean full_page


Remove all cache entries
""""""""""""""""""""""""

::

   n98-magerun.phar cache:flush

List Magento caches
"""""""""""""""""""

::

    n98-magerun.phar cache:list

Disable Magento cache
"""""""""""""""""""""

::

    n98-magerun.phar cache:disable

Enable Magento cache
""""""""""""""""""""

::

    n98-magerun.phar cache:enable


Demo Notice
"""""""""""

Toggle demo store notice

::

   n98-magerun.phar design:demo-notice <store_code>

List admin users
""""""""""""""""

::

    n98-magerun.phar admin:user:list

Change admin user password
""""""""""""""""""""""""""

::

    n98-magerun.phar admin:user:change-password [username] [password]

Disable admin notifications
"""""""""""""""""""""""""""

Toggle admin notifications.

::

    n98-magerun.phar admin:notifications

Toggle maintenance mode
"""""""""""""""""""""""

::

    n98-magerun.phar system:maintenance

Magento system info
""""""""""""""""""""

Provides info like the edition and version or the configured cache backends.

::

    n98-magerun.phar system:info

Magento Stores
""""""""""""""

Lists all store views.

::

    n98-magerun.phar system:store:list


Magento Websites
""""""""""""""

Lists all websites.

::

    n98-magerun.phar system:website:list

Magento modules
"""""""""""""""

Lists all installed modules with codepool and version

::

    n98-magerun.phar system:modules:list

Run Setup Scripts
"""""""""""""""""

Runs all setup scripts (no need to call frontend).
This command is useful if you update your system with enabled maintenance mode.

::

    n98-magerun.phar system:run-setup-scripts

Toggle Template Hints
"""""""""""""""""""""

Toggle debug template hints settings of a store

::

    n98-magerun.phar dev:template-hints <store_code>

Toggle Template Hints Blocks
""""""""""""""""""""""""""""

Toggle debug template hints blocks settings of a store

::

    n98-magerun.phar dev:template-hints-blocks <store_code>

Toggle Inline Translation
"""""""""""""""""""""""""

Toggle settings for shop frontend::

    n98-magerun.phar dev:translate:shop <store_code>

Toggle for admin area::

    n98-magerun.phar dev:translate:admin

Toggle Profiler
"""""""""""""""

Toggle profiler for debugging a store::

    n98-magerun.phar dev:profiler <store_code>

Toggle Symlinks
"""""""""""""""

Allow usage of symlinks for a store-view::

    n98-magerun.phar dev:symlinks <store_code>

Global scope can be set by not permitting store_code parameter::

    n98-magerun.phar dev:symlinks

List Extensions
"""""""""""""""

List and find connect extensions by a optional search string::

    n98-magerun.phar extension:list <search>

* Requires Magento's `mage` shell script.
* Does not work with Windows as operating system.

Install Extensions
""""""""""""""""""

Installs a connect extension by package key::

        n98-magerun.phar extension:install <package_key>

If the package could not be found a search for alternatives will be done.
If alternatives could be found you can select the package to install.

* Requires Magento's `mage` shell script.
* Does not work with Windows as operating system.

Magento Installer (Experimental)
""""""""""""""""""""""""""""""""

Since version 1.1.0 we deliver a Magento installer which does the following:

* Download Magento by a list of git repos and zip files (mageplus, magelte, official community packages).
* Try to create database if it does not exist.
* Installs Magento sample data if available (since version 1.2.0).
* Starts Magento installer
* Sets rewrite base in .htaccess file

::

    n98-magerun.phar install


Bash autocompletion
-------------------

Copy the file **bash_complete** as **n98-magerun.phar** in your bash autocomplete folder.
In my Ubuntu system this can be done with the following command::

    sudo cp bash_complete /etc/bash_completion.d/n98-magerun.phar

Advanced usage
--------------

Add your own commands
"""""""""""""""""""""

https://github.com/netz98/n98-magerun/wiki/Add-custom-commands

Overwrite default settings
""""""""""""""""""""""""""

Create the yaml config file **~/.n98-magerun.yaml**.
Now you can define overwrites. The original config file is **config.yaml** in the source root folder.

Change of i.e. default currency and admin users:

.. code-block:: yaml

    commands:
      N98\Magento\Command\Installer\InstallCommand:
        installation:
          defaults:
            currency: USD
            admin_username: myadmin
            admin_firstname: Firstname
            admin_lastname: Lastname
            admin_password: mydefaultSecret
            admin_email: defaultemail@example.com


Add own Magento repositories
""""""""""""""""""""""""""""

Create the yaml config file **~/.n98-magerun.yaml**.
Now you can define overwrites. The original config file is **config.yaml** in the source root folder.

Add you repo. The keys in the config file following the composer package structure.

Example::

    commands:
      N98\Magento\Command\Installer\InstallCommand:
        magento-packages:
          - name: my-magento-git-repository
            version: 1.x.x.x
            source:
              url: git://myserver/myrepo.git
              type: git
              reference: 1.x.x.x
            extra:
              sample-data: sample-data-1.6.1.0

          - name: my-zipped-magento
            version: 1.7.0.0
            dist:
              url: http://www.myserver.example.com/magento-1.7.0.0.tar.gz
              type: tar
            extra:
              sample-data: sample-data-1.6.1.0

How can you help?
-----------------

* Test the tool on Windows or OS X.
* Create issues if you find a bug or missing a feature.

Thanks to
---------

* Symfony2 Team for the great console component.
* Composer Team for the downloader backend and the self-update command.

Roadmap
-------

* Add your own installer sources
