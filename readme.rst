========================
netz98 magerun CLI tools
========================

The n98 magerun cli tools provides some handy tools to work with Magento from command line.

Compatibility
-------------
The tools are currently only tested with PHP 5.3.10 within Ubuntu 12.04 Linux and on Mac OS X.
If you are a Windows user you can help us with a quick test.

The tools should work with Magento 2 development branch.


Installation
------------

There are two ways to install the tools:

Download phar file
""""""""""""""""""

.. code-block:: sh

    wget https://raw.github.com/netz98/n98-magerun/master/n98-magerun.phar

You can make the .phar file executable.

.. code-block:: sh

    chmod +x ./n98-magerun.phar

If you want to use command system wide you can copy it to `/usr/local/bin`.

.. code-block:: sh

    sudo cp ./n98-magerun.phar /usr/local/bin/

**Debian / suhosin:**

On some debian systems with compiled in suhosin the phar extension must be added to a whitelist.

Add this to your php.ini file:

.. code-block:: ini

   suhosin.executor.include.whitelist="phar"


From source with composer
"""""""""""""""""""""""""

.. code-block:: sh

    #. Clone git repository
    git clone https://github.com/netz98/n98-magerun

    #. Download composer
    curl -s https://getcomposer.org/installer | php

    #. Let composer do all the work for you
    php ./composer.phar install

or

.. code-block:: sh

    php ./composer.phar create-project n98/magerun <folder>

    #. Run cli.php
    php vendor/bin/n98-magerun

It's recommended to install the .phar file system wide.

Update
------

Since version 1.1.0 we deliver a self-update script within the phar file::

   $ n98-magerun.phar self-update

If file was installed system wide do not forget "sudo".

Usage / Commands
----------------

All commands try to detect the current Magento root directory.
If you have multiple Magento installation you must change your working directory to
the preferred installation.

https://github.com/netz98/n98-magerun/wiki/Commands

You can list all available commands by::

   $ n98-magerun.phar list


If you don't have installed the .phar file system wide you can call it with the php cli interpreter::

   php n98-magerun.phar list

Print database information
"""""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar database:info

Dump database
"""""""""""""

Direct dump with mysqldump:

.. code-block:: sh

   $ n98-magerun.phar database:dump

Only the mysqldump command:

.. code-block:: sh

   $ n98-magerun.phar database:dump --only-command [filename]

Or directly to stdout:

.. code-block:: sh

   $ n98-magerun.phar database:dump --stdout

Database Console / MySQL Client
"""""""""""""""""""""""""""""""

Opens the MySQL console client with your database settings from local.xml

* Requires MySQL CLI tools

.. code-block:: sh

   $ n98-magerun.phar database:console

List Indexes
""""""""""""

.. code-block:: sh

   $ n98-magerun.phar index:list

Reindex a Index
"""""""""""""""

Index by indexer code. Code is optional. If you don't specify a code you can pick a indexer from a list.

.. code-block:: sh

   $ n98-magerun.phar index:reindex [code]

Reindex All
"""""""""""

Loops all magento indexes and triggers reindex.

.. code-block:: sh

   $ n98-magerun.phar index:reindex:all

Generate local.xml file
"""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar local-config:generate

Dump global xml config
""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar config:dump > extern_file.xml

Set Config
""""""""""

.. code-block:: sh

   $ n98-magerun.phar config:set [--scope[="..."]] [--scope-id[="..."]] path value

Arguments:
    path        The config path
    value       The config value

Options:
    --scope     The config value's scope (default: "default")
    --scope-id  The config value's scope ID (default: "0")

Get Config
""""""""""

.. code-block:: sh

   $ n98-magerun.phar config:get [--scope-id="..."] [path]

Arguments:
    path        The config path

Options:
    --scope-id  The config value's scope ID

Help:
    If path is not set, all available config items will be listed. path may contain wildcards (*)

List Magento cache status
"""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:list

Clean Magento cache
"""""""""""""""""""

Cleans expired cache entries.
If you like to remove all entries use `cache:flush`

.. code-block:: sh

   $ n98-magerun.phar cache:clean

Or only one cache type like i.e. full_page cache:

.. code-block:: sh

   $ n98-magerun.phar cache:clean full_page


Remove all cache entries
""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:flush

List Magento caches
"""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:list

Disable Magento cache
"""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:disable

Enable Magento cache
""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:enable


Demo Notice
"""""""""""

Toggle demo store notice

.. code-block:: sh

   $ n98-magerun.phar design:demo-notice [store_code]

List admin users
""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:list

Create admin user
"""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:create [username] [email] [password] [firstname] [lastname]


Change admin user password
""""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:change-password [username] [password]

Disable admin notifications
"""""""""""""""""""""""""""

Toggle admin notifications.

.. code-block:: sh

   $ n98-magerun.phar admin:notifications

Toggle maintenance mode
"""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar sys:maintenance

Magento system info
""""""""""""""""""""

Provides info like the edition and version or the configured cache backends.

.. code-block:: sh

   $ n98-magerun.phar sys:info

Magento Stores
""""""""""""""

Lists all store views.

.. code-block:: sh

   $ n98-magerun.phar sys:store:list

Magento Store Config - BaseURLs
"""""""""""""""""""""""""""""""

Lists base urls for each store.

.. code-block:: sh

   $ n98-magerun.phar sys:store:config:base-url:list

Magento Websites
""""""""""""""

Lists all websites.

.. code-block:: sh

   $ n98-magerun.phar sys:website:list

Magento Cronjobs
""""""""""""""""

Lists all cronjobs defined in config.xml files.

.. code-block:: sh

   $ n98-magerun.phar sys:cron:list

List URLs
"""""""""

.. code-block:: sh

   $ sys:url:list [--add-categories] [--add-products] [--add-cmspages] [--add-all] [stores] [linetemplate]

Examples:

- Create a list of product urls only:

.. code-block:: sh

   $ n98-magerun.phar system:urls:list --add-products 4

- Create a list of all products, categories and cms pages of store 4 and 5 separating host and path (e.g. to feed a jmeter csv sampler):

.. code-block:: sh

   $ n98-magerun.phar system:urls:list --add-all 4,5 '{host},{path}' > urls.csv

- The "linetemplate" can contain all parts "parse_url" return wrapped in '{}'. '{url}' always maps the complete url and is set by default


Run Setup Scripts
"""""""""""""""""

Runs all setup scripts (no need to call frontend).
This command is useful if you update your system with enabled maintenance mode.

.. code-block:: sh

   $ n98-magerun.phar sys:setup:run

Compare Setup Versions
""""""""""""""""""""""

Compares module version with saved setup version in `core_resource` table and displays version mismatch.

.. code-block:: sh

   $ n98-magerun.phar sys:setup:compare-versions [--ignore-data]

System Check
""""""""""""

- Checks missing files and folders
- Security
- PHP Extensions (Required and Bytecode Cache)
- MySQL InnoDB Engine

.. code-block:: sh

   $ n98-magerun.phar sys:check

CMS: Toggle Banner
""""""""""""""""""

Hide/Show CMS Banners

.. code-block:: sh

   $ n98-magerun.phar cms:banner:toggle <banner_id>

CMS: Publish a page
"""""""""""""""""""

Publishes a page by page id and revision.

.. code-block:: sh

   $ n98-magerun.phar cms:page:publish <page_id> <revision_id>

Useful to automatically publish a page by a cron job.


Toggle Template Hints
"""""""""""""""""""""

Toggle debug template hints settings of a store

.. code-block:: sh

   $ n98-magerun.phar dev:template-hints [store_code]

Toggle Template Hints Blocks
""""""""""""""""""""""""""""

Toggle debug template hints blocks settings of a store

.. code-block:: sh

   $ n98-magerun.phar dev:template-hints-blocks [store_code]

Toggle Inline Translation
"""""""""""""""""""""""""

Toggle settings for shop frontend:

.. code-block:: sh

   $ n98-magerun.phar dev:translate:shop [store_code]

Toggle for admin area:

.. code-block:: sh

   $ n98-magerun.phar dev:translate:admin

Toggle Profiler
"""""""""""""""

Toggle profiler for debugging a store:

.. code-block:: sh

   $ n98-magerun.phar dev:profiler [store_code]

Toggle Development Logs
"""""""""""""""""""""""

Activate/Deactivate system.log and exception.log for a store:

.. code-block:: sh

   $ n98-magerun.phar dev:log [store_code]

Toggle Symlinks
"""""""""""""""

Allow usage of symlinks for a store-view:

.. code-block:: sh

   $ n98-magerun.phar dev:symlinks <store_code>

Global scope can be set by not permitting store_code parameter:

.. code-block:: sh

   $ n98-magerun.phar dev:symlinks

Create Module Skel
""""""""""""""""""

Creates an empty module and registers it in current magento shop:

.. code-block:: sh

   $ n98-magerun.phar dev:module:create [--add-blocks] [--add-helpers] [--add-models] [--add-all] [--modman] vendorNamespace moduleName [codePool]

Code-Pool defaults to `local`.


Example:

.. code-block:: sh

   $ n98-magerun.phar dev:module:create MyVendor MyModule


* `--modman` option creates a new folder based on `vendorNamespace` and `moduleName` argument.
Run this command inside your `.modman` folder.

* --add-all option add blocks, helpers and models.

List Modules
""""""""""""

Lists all installed modules with codepool and version

.. code-block:: sh

   $ n98-magerun.phar dev:module:list

Rewrite List
""""""""""""

Lists all registered class rewrites::

   $ n98-magerun.phar dev:module:rewrite:list

Rewrite Conflicts
"""""""""""""""""

Lists all duplicated rewrites and tells you which class is loaded by Magento.
The command checks class inheritance in order of your module dependencies.

.. code-block:: sh

   $ n98-magerun.phar dev:module:rewrite:conflicts [--log-junit="..."]

* If a filename with `--log-junit` option is set the tool generates an XML file and no output to *stdout*.

Observer List
"""""""""""""

Lists all registered observer by type.

.. code-block:: sh

   $ n98-magerun.phar dev:module:observer:list [type]

Type is one of "adminhtml", "global", "frontend".


List Extensions
"""""""""""""""

List and find connect extensions by a optional search string:

.. code-block:: sh

   $ n98-magerun.phar extension:list <search>

* Requires Magento's `mage` shell script.
* Does not work with Windows as operating system.

Install Extensions
""""""""""""""""""

Installs a connect extension by package key:

.. code-block:: sh

   $ n98-magerun.phar extension:install <package_key>

If the package could not be found a search for alternatives will be done.
If alternatives could be found you can select the package to install.

* Requires Magento's `mage` shell script.
* Does not work with Windows as operating system.

Download Extensions
"""""""""""""""""""

Downloads connect extensions by package key:

.. code-block:: sh

   $ n98-magerun.phar extension:download <search>

* Requires Magento's `mage` shell script.
* Does not work with Windows as operating system.

Upgrade Extensions
""""""""""""""""""

Upgrade connect extensions by package key:

.. code-block:: sh

   $ n98-magerun.phar extension:upgrade <search>

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

.. code-block:: sh

   $ n98-magerun.phar install

Autocompletion
--------------

Bash
""""

Copy the file **bash_complete** as **n98-magerun.phar** in your bash autocomplete folder.
In my Ubuntu system this can be done with the following command:

.. code-block:: sh

   $ sudo cp autocompletion/bash/bash_complete /etc/bash_completion.d/n98-magerun.phar


PHPStorm
""""""""

An commandline tool autocompletion XML file for PHPStorm exists in subfolder **autocompletion/phpstorm**.
Copy **n98_magerun.xml** in your phpstorm config folder.

Linux: ~/.WebIde50/config/commandlinetools

You can also add the XML content over settings menu.
For further instructions read this blog post: http://blog.jetbrains.com/webide/2012/10/integrating-composer-command-line-tool-with-phpstorm/

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

* Add new commands
* Send me some proposals if you miss anything
* Create issues if you find a bug or missing a feature.

Thanks to
---------

* Symfony2 Team for the great console component.
* Composer Team for the downloader backend and the self-update command.

Roadmap
-------

* dev:event:list - List all magento events
* List more infos like Base URLs.
* Change BaseURL from CLI.