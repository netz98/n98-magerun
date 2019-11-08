======================================
netz98 magerun CLI tools for Magento 1
======================================

The n98 magerun cli tools provides some handy tools to work with Magento from command line.


Build Status
------------

+------------------------+-----------------------------------------------------------------------------------------------+
| **Latest Release**     | .. image:: https://travis-ci.org/netz98/n98-magerun.png?branch=master                         |
|                        |    :target: https://travis-ci.org/netz98/n98-magerun                                          |
|                        | .. image:: https://scrutinizer-ci.com/g/netz98/n98-magerun/badges/quality-score.png?b=master  |
|                        |    :target: https://scrutinizer-ci.com/g/netz98/n98-magerun/                                  |
|                        | .. image:: https://poser.pugx.org/n98/magerun/v/stable.png                                    |
|                        |    :target: https://packagist.org/packages/n98/magerun                                        |
+------------------------+-----------------------------------------------------------------------------------------------+
| **Development Branch** | .. image:: https://travis-ci.org/netz98/n98-magerun.png?branch=develop                        |
|                        |    :target: https://travis-ci.org/netz98/n98-magerun                                          |
|                        | .. image:: https://circleci.com/gh/netz98/n98-magerun/tree/develop.svg?style=shield           |
|                        |    :target: https://circleci.com/gh/netz98/n98-magerun/tree/develop                           |
|                        | .. image:: https://scrutinizer-ci.com/g/netz98/n98-magerun/badges/quality-score.png?b=develop |
|                        |    :target: https://scrutinizer-ci.com/g/netz98/n98-magerun/?branch=develop                   |
|                        | .. image:: https://codecov.io/github/netz98/n98-magerun/coverage.svg?branch=develop           |
|                        |    :target: https://codecov.io/github/netz98/n98-magerun?branch=develop                       |
+------------------------+-----------------------------------------------------------------------------------------------+

Development is done in **develop** branch.

This software is only running with Magento 1.

If you use Magento 2 please use another stable version (https://github.com/netz98/n98-magerun2).

Compatibility
-------------
The tools will automatically be tested for multiple PHP versions. It's currently running in various Linux distributions and Mac OS X.
Microsoft Windows is not fully supported (some Commands like `db:dump` or `install` are excluded).

Installation
------------

There are three ways to install the tools:

Download and Install Phar File
""""""""""""""""""""""""""""""

Download the latest stable N98-Magerun phar-file from the file-server_:

.. code-block:: sh

   wget https://files.magerun.net/n98-magerun.phar

or if you prefer to use Curl:

.. code-block:: sh

   curl -O https://files.magerun.net/n98-magerun.phar

Verify the download by comparing the SHA256 checksum with the one on the website at https://files.magerun.net/:

.. code-block:: sh

    shasum -a256 n98-magerun.phar

If it shows the same checksum as on the website, you downloaded the file successfully.

Now you can make the phar-file executable:

.. code-block:: sh

    chmod +x ./n98-magerun.phar

The base-installation is now complete and you can verify it:

.. code-block:: sh

    ./n98-magerun.phar --version

The command should execute successfully and show you the version number of N98-Magerun like:

.. code-block:: sh

    n98-magerun version 1.97.0 by netz98 new media GmbH

You now have successfully installed Magerun! You can tailor the installation further like installing it system-wide and
enable autocomplete - read on for more information about these and other features.

If you want to use the command system wide you can copy it to `/usr/local/bin`.

.. code-block:: sh

    sudo cp ./n98-magerun.phar /usr/local/bin/

**Debian / suhosin:**

On some Debian systems with compiled in suhosin the phar extension must be added to a whitelist.

Add this to your php.ini file:

.. code-block:: ini

   suhosin.executor.include.whitelist="phar"

**You don't like the filename?**

Just rename it to whatever you want. Or better: create an alias so that the original command name still works. This can
be useful if you exchange scripts that are making use of magerun with other users as the canonical name is
`n98-magerun.phar`, Some common aliases amongst the user-base are `magerun` or just `mr` even.


.. _file-server: https://files.magerun.net/

Install with Composer
"""""""""""""""""""""
Require Magerun within the Magento (or any other) project and you can then
execute it from the vendor’s bin folder:

.. code-block:: sh

    composer require n98/magerun
    # ...
    ./vendor/bin/n98-magerun --version
    n98-magerun version 1.97.0 by netz98 new media GmbH

Alternative source install:

https://github.com/netz98/n98-magerun/wiki/Install-from-source-with-Composer

Install with Homebrew
"""""""""""""""""""""

First you need to have homebrew installed: http://brew.sh/

Install homebrew-php tap: https://github.com/Homebrew/homebrew-php#installation

Once homebrew and the tap are installed, you can install the tools with it:

.. code-block:: sh

    brew install n98-magerun

You can now use the tools:

.. code-block:: sh

    $ n98-magerun {command}

Update
------

Since version 1.1.0 we deliver a self-update script within the phar file::

   $ n98-magerun.phar self-update

If file was installed system wide do not forget "sudo".

See it in action: http://youtu.be/wMHpfKD9vjM

Autocompletion
--------------

Files for autocompletion with Magerun can be found inside the folder `res/autocompletion`, In
the following some more information about two specific ones (Bash, Phpstorm), there are
more (e.g. Fish, Zsh).

Bash
""""

Bash completion is available pre-generated, all commands and their respective
options are availble on tab. To get completion for an otion type two dashes
("--") and then tab.

To install the completion files, copy **n98-magerun.phar.bash** to your bash
compatdir folder for autocompletion.

On my Ubuntu system this can be done with the following command:

.. code-block:: sh

   # cp res/autocompletion/bash/n98-magerun.phar.bash /etc/bash_completion.d

The concrete folder can be obtained via pkg-config:

.. code-block:: sh

    # pkg-config --variable=compatdir bash-completion

Detailed information is available in the bash-completions FAQ: https://github.com/scop/bash-completion#faq

PHPStorm
""""""""

A commandline tool autocompletion XML file for PHPStorm exists in subfolder **res/autocompletion/phpstorm**.
Copy **n98_magerun.xml** into your phpstorm config folder.

Linux and Mac: ~/.WebIde80/config/componentVersions

You can also add the XML content over settings menu.
For further instructions read this blog post: http://blog.jetbrains.com/webide/2012/10/integrating-composer-command-line-tool-with-phpstorm/

Usage / Commands
----------------

All commands try to detect the current Magento root directory.
If you have multiple Magento installations you must change your working directory to
the preferred installation.

https://github.com/netz98/n98-magerun/wiki/Commands

You can list all available commands by::

   $ n98-magerun.phar list


If you don't have the .phar file installed system wide you can call it with the PHP CLI interpreter::

   php n98-magerun.phar list


Global config parameters:

  --root-dir
      Force Magento root dir. No auto detection.
  --skip-config
      Do not load any custom config.
  --skip-root-check
      Do not check if n98-magerun runs as root.
  --developer-mode
      Instantiate Magento in Developer Mode


Open Shop in Browser
""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar open-browser [store]

Customer Info
"""""""""""""

Loads basic customer info by email address.

.. code-block:: sh

   $ n98-magerun.phar  customer:info [email] [website]


Create customer
"""""""""""""""

Creates a new customer/user for shop frontend.

.. code-block:: sh

   $ n98-magerun.phar  customer:create [email] [password] [firstname] [lastname] [website]

Example:

.. code-block:: sh

  $ n98-magerun.phar customer:create foo@example.com password123 John Doe base

Delete Customers
""""""""""""""""

This will delete a customer by a given Id/Email, delete all customers or delete all customers in a range of Ids.

.. code-block:: sh

   $ n98-magerun.phar delete [-a|--all] [-f|--force] [-r|--range] [id]

Examples:

.. code-block:: sh

   $ n98-magerun.phar customer:delete 1                   # Will delete customer with Id 1
   $ n98-magerun.phar customer:delete mike@example.com    # Will delete customer with that email
   $ n98-magerun.phar customer:delete --all               # Will delete all customers
   $ n98-magerun.phar customer:delete --range             # Will prompt for start and end Ids for batch deletion

Generate Dummy Customers
""""""""""""""""""""""""

Generate dummy customers. You can specify a count and a locale.

.. code-block:: sh

  $ n98-magerun.phar customer:create:dummy count locale [website]


Supported Locales:

    * cs_CZ
    * ru_RU
    * bg_BG
    * en_US
    * it_IT
    * sr_RS
    * sr_Cyrl_RS
    * sr_Latn_RS
    * pl_PL
    * en_GB
    * de_DE
    * sk_SK
    * fr_FR
    * es_AR
    * de_AT

List Customers
""""""""""""""

List customers. The output is limited to 1000 (can be changed by overriding config).
If search parameter is given the customers are filtered (searchs in firstname, lastname and email).

.. code-block:: sh

   $ n98-magerun.phar  customer:list [--format[="..."]] [search]

Change customer password
""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar customer:change-password [email] [password] [website]

- Website parameter must only be given if more than one websites are available.

Print database information
"""""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar db:info [setting]

**Arguments**

    setting               Only output value of named setting


Dump database
"""""""""""""

Dumps configured Magento database with `mysqldump`.

* Requires MySQL CLI tools

**Arguments**

    filename        Dump filename

**Options**

  --add-time
        Adds time to filename (only if filename was not provided)

  --compression (-c)
        Compress the dump file using one of the supported algorithms

  --only-command
        Print only mysqldump command. Do not execute

  --print-only-filename
        Execute and prints not output except the dump filename

  --no-single-transaction
        Do not use single-transaction (not recommended, this is blocking)

  --human-readable
        Use a single insert with column names per row.

  --stdout
        Dump to stdout

  --strip
        Tables to strip (dump only structure of those tables)

  --force (-f)
        Do not prompt if all options are defined


.. code-block:: sh

   $ n98-magerun.phar db:dump

Only the mysqldump command:

.. code-block:: sh

   $ n98-magerun.phar db:dump --only-command [filename]

Or directly to stdout:

.. code-block:: sh

   $ n98-magerun.phar db:dump --stdout

Use compression (gzip cli tool has to be installed):

.. code-block:: sh

   $ n98-magerun.phar db:dump --compression="gzip"

Stripped Database Dump
^^^^^^^^^^^^^^^^^^^^^^

Dumps your database and excludes some tables. This is useful i.e. for development.

Separate each table to strip by a space.
You can use wildcards like * and ? in the table names to strip multiple tables.
In addition you can specify pre-defined table groups, that start with an @
Example: "dataflow_batch_export unimportant_module_* @log"

.. code-block:: sh

   $ n98-magerun.phar db:dump --strip="@stripped"

Available Table Groups:

* @admin Admin tables
* @log Log tables
* @dataflowtemp Temporary tables of the dataflow import/export tool
* @importexporttemp Temporary tables of the Import/Export module
* @stripped Standard definition for a stripped dump (logs, sessions, dataflow and importexport)
* @sales Sales data (orders, invoices, creditmemos etc)
* @customers Customer data
* @trade Current trade data (customers and orders). You usally do not want those in developer systems.
* @search Search related tables (catalogsearch_)
* @development Removes logs, sessions, trade data and admin users so developers do not have to work with real customer data or admin user accounts
* @idx Tables with _idx suffix and index event tables

Extended: https://github.com/netz98/n98-magerun/wiki/Stripped-Database-Dumps

See it in action: http://youtu.be/ttjZHY6vThs

Database Import
"""""""""""""""

Imports an SQL file with mysql cli client into current configured database.

* Requires MySQL CLI tools

Arguments:
    filename        Dump filename

Options:
     --compression (-c)       The compression of the specified file
     --only-command           Print only mysql command. Do not execute

.. code-block:: sh

   $ n98-magerun.phar db:dump

.. code-block:: sh

   $ n98-magerun.phar db:import [--only-command] [filename]

Use decompression (gzip cli tool has to be installed):

.. code-block:: sh

   $ n98-magerun.phar db:import --compression="gzip" [filename]

Optimize "human readable" dump:

.. code-block:: sh

   $ n98-magerun.phar db:import --optimize [filename]

Database Console / MySQL Client
"""""""""""""""""""""""""""""""

Opens the MySQL console client with your database settings from local.xml

* Requires MySQL CLI tools

.. code-block:: sh

   $ n98-magerun.phar db:console [--no-auto-rehash]

  --no-auto-rehash
      synonym for calling *mysql* client with the -A parameter to skip hashing for object auto-completion.

Database Create
"""""""""""""""

Create currently configured database

.. code-block:: sh

   $ n98-magerun.phar db:create

Database Drop
"""""""""""""

Drops the database configured in local.xml.

* Requires MySQL CLI tools

.. code-block:: sh

   $ n98-magerun.phar db:drop  [-f|--force]

Database Query
""""""""""""""

Executes an SQL query on the current configured database. Wrap your SQL in
single or double quotes.

If your query produces a result (e.g. a SELECT statement), the output of the
mysql cli tool will be returned.

* Requires MySQL CLI tools

Arguments:
    query        SQL query

Options:
     --only-command           Print only mysql command. Do not execute

.. code-block:: sh

   $ n98-magerun.phar db:query [--only-command] [query]


Database Variables
""""""""""""""""""

See the most important MySQL variables of your Magento instance.

.. code-block:: sh

   $ n98-magerun.phar db:variables [--format[="..."]] [--rounding[="..."]] [--no-description] [search]

Database Status
"""""""""""""""

This command is useful to print important server status information about the current database.

.. code-block:: sh

   $ n98-magerun.phar [--format[="..."]] [--rounding[="..."]] [--no-description] [search]

Dump Media folder
"""""""""""""""""

Creates a ZIP archive with media folder content.

.. code-block:: sh

   $ n98-magerun.phar media:dump [--strip] [filename]

If strip option is set, the following folders are excluded:

* js (combined js files)
* css (combined css files)
* catalog/product/cache

Create Gift Card Pool
"""""""""""""""""""""

Creates a new giftcard pool

.. code-block:: sh

   $ n98-magerun.phar giftcard:pool:generate

Create a Gift Card
""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar giftcard:create [--website[="..."]] amount

You may specify a website ID or use the default

View Gift Card Information
""""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar giftcard:info [--format[="..."]] code

Remove a Gift Card
""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar giftcard:remove code

List Indexes
""""""""""""

.. code-block:: sh

   $ n98-magerun.phar index:list [--format[="..."]]

Reindex a Index
"""""""""""""""

Index by indexer code. Code is optional. If you don't specify a code you can pick a indexer from a list.

.. code-block:: sh

   $ n98-magerun.phar index:reindex [code]


Since 1.75.0 it's possible to run mutiple indexers by seperating code with a comma.

i.e.

.. code-block:: sh

   $ n98-magerun.phar index:reindex catalog_product_attribute,tag_summary

If no index is provided as argument you can select indexers from menu by "number" like "1,3" for first and third
indexer.

Reindex All
"""""""""""

Loops all Magento indexes and triggers reindex.

.. code-block:: sh

   $ n98-magerun.phar index:reindex:all

List Enterprise Mview Changelog Indexes
"""""""""""""""""""""""""""""""""""""""

Lists the Mview indexers available, as well as their current version and how many are in the changelog queue .

.. code-block:: sh

   $ n98-magerun.phar index:list:mview [--format[="..."]]

Reindex an Enterprise Mview Changelog Index
"""""""""""""""""""""""""""""""""""""""""""

Index by Mview table code. This will ignore all locks and trigger the changelog indexer.

.. code-block:: sh

   $ n98-magerun.phar index:reindex:mview [table_code]


Generate local.xml file
"""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar local-config:generate

Config Dump
"""""""""""

Dumps merged XML configuration to stdout. Useful to see all the XML.

.. code-block:: sh

   $ n98-magerun.phar [xpath]

Examples
^^^^^^^^

Config of catalog module:

.. code-block:: sh

   $ n98-magerun.phar config:dump global/catalog


See module order in XML:

.. code-block:: sh

   $ n98-magerun.phar config:dump modules


Write output to file:

.. code-block:: sh

   $ n98-magerun.phar config:dump > extern_file.xml


Set Config
""""""""""

.. code-block:: sh

   $ n98-magerun.phar config:set [--scope[="..."]] [--scope-id[="..."]] [--encrypt] [--force] path value

Arguments:
    path        The config path
    value       The config value

Options:
    --scope     The config value's scope (default: "default" | Can be "default", "websites", "stores")
    --scope-id  The config value's scope ID (default: "0")
    --encrypt   Encrypt the config value using local.xml's crypt key
    --force     Allow creation of non-standard scope-id's for websites and stores

Get Config
""""""""""

.. code-block:: sh

   $ n98-magerun.phar config:get [--scope="..."] [--scope-id="..."] [--decrypt] [--format[="..."]] [path]

Arguments:
    path        The config path

Options:
    --scope             The config value's scope (default, websites, stores)
    --scope-id          The config value's scope ID
    --decrypt           Decrypt the config value using local.xml's crypt key
    --update-script     Output as update script lines
    --magerun-script    Output for usage with config:set
    --format            Output as json, xml or csv

Help:
    If path is not set, all available config items will be listed. path may contain wildcards (*)

Example:

.. code-block:: sh

   $ n98-magerun.phar config:get web/* --magerun-script

Delete Config
"""""""""""""

.. code-block:: sh

   $ n98-magerun.phar config:delete [--scope[="..."]] [--scope-id[="..."]] [--all] [--force] path

Arguments:
    path        The config path

Options:
    --scope     The config scope (default, websites, stores)
    --scope-id  The config value's scope ID
    --all       Deletes all entries of a path (ignores --scope and --scope-id)
    --force     Allow deletion of non-standard scope-id's for websites and stores

Config Search
"""""""""""""

Search system configuration descriptions.

 .. code-block:: sh

   $ n98-magerun.phar config:search text


List Magento cache status
"""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:list

Clean Magento cache
"""""""""""""""""""

Cleans expired cache entries.

If you would like to clean only one cache type:

.. code-block:: sh

   $ n98-magerun.phar cache:clean [--reinit] [--no-reinit] [<code>]

If you would like to clean multiple cache types at once:

.. code-block:: sh

   $ n98-magerun.phar cache:clean [--reinit] [--no-reinit] [<code>] [<code>] ...

Options:
    --reinit Reinitialise the config cache after cleaning (Default)
    --no-reinit Don't reinitialise the config cache after cleaning. This will override --reinit.

If you would like to remove all cache entries use `cache:flush`

Run `cache:list` command to see all codes.

Remove all cache entries
""""""""""""""""""""""""

Flush the entire cache.

.. code-block:: sh

   $ n98-magerun.phar cache:flush [--reinit] [--no-reinit]

Options:
    --reinit Reinitialise the config cache after flushing (Default)
    --no-reinit Don't reinitialise the config cache after flushing. This will override --reinit.

List Magento caches
"""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:list [--format[="..."]]

Disable Magento cache
"""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:disable [code]

If no code is specified, all cache types will be disabled.
Run `cache:list` command to see all codes.

Enable Magento cache
""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar cache:enable [code]

If no code is specified, all cache types will be enabled.
Run `cache:list` command to see all codes.

Cache Report
""""""""""""

This command let you investigate what's stored inside your cache.
It prints out a table with cache IDs.

.. code-block:: sh

   $ cache:report [-t|--tags] [-m|--mtime] [--filter-id[="..."]] [--filter-tag[="..."]] [--fpc]

Cache View
""""""""""

Prints stored cache entry by ID.

.. code-block:: sh

   $ cache:view [--unserialize] [--fpc] id

If value is serialized you can force a pretty output with --unserialize option.

Toggle CMS Block
""""""""""""""""

Toggle "is_active" on a cms block

.. code-block:: sh

   $ n98-magerun.phar cms:block:toggle [block_id]

"block_id" can be an entity id or an "identifier"

List CMS Blocks
""""""""""""""""

List all CMS blocks

.. code-block:: sh

   $ n98-magerun.phar cms:block:list [--format[="..."]]

Demo Notice
"""""""""""

Toggle demo store notice

.. code-block:: sh

   $ n98-magerun.phar design:demo-notice [store_code]

List admin users
""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:list [--format[="..."]]

Create admin user
"""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:create [username] [email] [password] [firstname] [lastname] [role]


Change admin user password
""""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:change-password [username] [password]

Delete admin user
"""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:delete [email|username] [-f]

ID can be e-mail or username. The command will attempt to find the user by username first and if it cannot be found it
will attempt to find the user by e-mail. If ID is omitted you will be prompted for it. If the force parameter "-f" is
omitted you will be prompted for confirmation.

Toggle admin user active state
""""""""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:change-status [--activate] [--deactivate] [email|username]

Toggles the active status of an backend user. ID can be e-mail or username. The command will attempt to find the
user by username first and if it cannot be found it will attempt to find the user by e-mail. If ID is omitted you
will be prompted for it.

Lock admin user
"""""""""""""""""
.. code-block:: sh

   $ n98-magerun.phar admin:user:lock [username] [lifetime]

Locks an admin user for the number of days specified in `[lifetime]`. If not provided, the lifetime will default to
31 days.

Lock all admin users
"""""""""""""""""
.. code-block:: sh

   $ n98-magerun.phar admin:user:lockdown [lifetime] [--dry-run]

Locks all admin users in the system for the number of days specified in `[lifetime]`. As above, if not provided it will
default to 31 days.

Use with caution! Use the `--dry-run` option to test first.

Unlock admin user
"""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar admin:user:unlock [username]

Releases the password lock on an admin (leave blank to unlock all admins).

Disable admin notifications
"""""""""""""""""""""""""""

Toggle admin notifications.

.. code-block:: sh

   $ n98-magerun.phar admin:notifications

Maintenance mode
""""""""""""""""

If no option is provided it toggles the mode on every call.

.. code-block:: sh

   $ n98-magerun.phar sys:maintenance [--on] [--off]

Magento system info
"""""""""""""""""""

Provides info like the edition and version or the configured cache backends.

.. code-block:: sh

   $ n98-magerun.phar sys:info [key]

Print only one value like the version.

.. code-block:: sh

   $ n98-magerun.phar sys:info version

Magento Stores
""""""""""""""

Lists all store views.

.. code-block:: sh

   $ n98-magerun.phar sys:store:list [--format[="..."]]

Magento Store Config - BaseURLs
"""""""""""""""""""""""""""""""

Lists base urls for each store.

.. code-block:: sh

   $ n98-magerun.phar sys:store:config:base-url:list [--format[="..."]]

Magento Websites
""""""""""""""""

Lists all websites.

.. code-block:: sh

   $ n98-magerun.phar sys:website:list [--format[="..."]]

List Cronjobs
"""""""""""""

Lists all cronjobs defined in config.xml files.

.. code-block:: sh

   $ n98-magerun.phar sys:cron:list [--format[="..."]]

Run Cronjob
"""""""""""

Runs a cronjob by code.

.. code-block:: sh

   $ n98-magerun.phar sys:cron:run [--schedule] [job]

If no `job` argument is passed you can select a job from a list.
See it in action: http://www.youtube.com/watch?v=QkzkLgrfNaM
If option schedule is present, cron is not launched, but just scheduled immediately in magento crontab.

Cronjob History
"""""""""""""""

Last executed cronjobs with status.

.. code-block:: sh

   $ n98-magerun.phar sys:cron:history [--format[="..."]] [--timezone[="..."]]

List URLs
"""""""""

.. code-block:: sh

   $ n98-magerun.phar sys:url:list [--add-categories] [--add-products] [--add-cmspages] [--add-all] [stores] [linetemplate]

Examples:

- Create a list of product urls only:

.. code-block:: sh

   $ n98-magerun.phar sys:url:list --add-products 4

- Create a list of all products, categories and cms pages of store 4 and 5 separating host and path (e.g. to feed a jmeter csv sampler):

.. code-block:: sh

   $ n98-magerun.phar sys:url:list --add-all 4,5 '{host},{path}' > urls.csv

- The "linetemplate" can contain all parts "parse_url" return wrapped in '{}'. '{url}' always maps the complete url and is set by default


Run Setup Scripts
"""""""""""""""""

Runs all setup scripts (no need to call frontend).
This command is useful if you update your system with enabled maintenance mode.

.. code-block:: sh

   $ n98-magerun.phar sys:setup:run

Run Setup Scripts Incrementally
"""""""""""""""""""""""""""""""

Runs setup scripts incrementally. (no need to call frontend).
This command runs each new setup script individually in order to increase the transparency of the setup resource system, and reduce the chances of a PHP failure creating an invalid database state.

.. code-block:: sh

   $ n98-magerun.phar sys:setup:incremental [--stop-on-error]

Compare Setup Versions
""""""""""""""""""""""

Compares module version with saved setup version in `core_resource` table and displays version mismatch.

.. code-block:: sh

   $ n98-magerun.phar sys:setup:compare-versions [--ignore-data] [--errors-only] [--log-junit="..."] [--format[="..."]]

* If a filename with `--log-junit` option is set the tool generates an XML file and no output to *stdout*.
* If status errors are found this will return an exit status of 1 rather than 0, making it perfect for hooking into deployment scripts.

Change Setup Version
""""""""""""""""""""

Changes the version of one or all module resource setups. This command is useful if you want to re-run an upgrade
script again possibly due to debugging. Alternatively you would have to alter the row in the database manually.


.. code-block:: sh

   $ n98-magerun.phar sys:setup:change-version module version [setup]

Setup argument default is "all resources" for the given module.

Remove Setup Version
""""""""""""""""""""

Removes the entry for one or all module resource setups. This command is useful if you want to re-run an install
script again possibly due to debugging. Alternatively you would have to remove the row from the database manually.

.. code-block:: sh

   $ n98-magerun.phar sys:setup:remove module [setup]

Setup argument default is "all resources" for the given module.

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

Interactive Development Console
"""""""""""""""""""""""""""""""

Opens PHP interactive shell with initialized Magento Admin-Store.

.. code-block:: sh

   $ n98-magerun.phar dev:console

See it in action: http://www.youtube.com/watch?v=zAWpRpawTGc

The command is only available for PHP 5.4 users.

CSS Merging
""""""""""""""

Toggle CSS merging settings of a store

.. code-block:: sh

   $ n98-magerun.phar dev:merge-css [store_code]

JS Merging
""""""""""""""

Toggle JS merging settings of a store

.. code-block:: sh

   $ n98-magerun.phar dev:merge-js [store_code]

Template Hints
""""""""""""""

Toggle debug template hints settings of a store

.. code-block:: sh

   $ n98-magerun.phar dev:template-hints [store_code]

Template Hints Blocks
"""""""""""""""""""""

Toggle debug template hints blocks settings of a store

.. code-block:: sh

   $ n98-magerun.phar dev:template-hints-blocks [store_code]

Inline Translation
""""""""""""""""""

Toggle settings for shop frontend:

.. code-block:: sh

   $ n98-magerun.phar dev:translate:shop [store_code]

Toggle for admin area:

.. code-block:: sh

   $ n98-magerun.phar dev:translate:admin

Export Inline Translation
"""""""""""""""""""""""""

Exports saved database translation data into a file.

.. code-block:: sh

   $ n98-magerun.phar dev:translate:export [locale] [filename]

Profiler
""""""""

Toggle profiler for debugging a store:

.. code-block:: sh

   $ n98-magerun.phar dev:profiler [--on] [--off] [--global] [store]

Email Template Usage
""""""""""""""""""""

Display a report of use transactional email templates:

.. code-block:: sh

   $ n98-magerun.phar dev:email-template:usage --format[=FORMAT]

Development Logs
""""""""""""""""

Activate/Deactivate system.log and exception.log for a store:

.. code-block:: sh

   $ n98-magerun.phar dev:log [--on] [--off] [--global] [store]

Show size of a log file:

.. code-block:: sh

   $ n98-magerun.phar dev:log:size [--human] [log_filename]

Activate/Deactivate MySQL query logging via lib/Varien/Db/Adapter/Pdo/Mysql.php

.. code-block:: sh

   $ n98-magerun.phar dev:log:db [--on] [--off]

Setup Script Generation
"""""""""""""""""""""""

Generate Script for attributes:

.. code-block:: sh

   $ n98-magerun.phar dev:setup:script:attribute entityType attributeCode

i.e.

.. code-block:: sh

   $ n98-magerun.phar dev:setup:script:attribute catalog_product color

Currently only *catalog_product* entity type is supported.

EAV Attributes
""""""""""""""

List all EAV attributes:

.. code-block:: sh

   $ n98-magerun.phar eav:attribute:list [--filter-type[="..."]] [--add-source] [--add-backend] [--format[="..."]]

View the data for a particular attribute:

.. code-block:: sh

   $ n98-magerun.phar eav:attribute:view [--format[="..."]] entityType attributeCode

Remove an attribute:

.. code-block:: sh

   $ n98-magerun.phar eav:attribute:remove entityType attributeCode

You can also remove multiple attributes in one go if they are of the same type

.. code-block:: sh

   $ n98-magerun.phar eav:attribute:remove entityType attributeCode1 attributeCode2 ... attributeCode10


Development IDE Support
"""""""""""""""""""""""

**PhpStorm Code Completion** -> Meta file generation.

.. code-block:: sh

   $ n98-magerun.phar dev:ide:phpstorm:meta [--meta-version=(old|2016.2+)] [--stdout]

Generates meta data file for PhpStorm auto completion (default version : 2016.2+)

Reports
"""""""

Prints count of reports in var/reports folder.

.. code-block:: sh

   $ n98-magerun.phar dev:report:count

Resolve/Lookup Class Names
""""""""""""""""""""""""""

Resolves the given type and grouped class name to a class name, useful for debugging rewrites.

If the resolved class doesn't exist, an info message will be displayed.

.. code-block:: sh

   $ n98-magerun.phar dev:class:lookup <block|model|helper> <name>

Example:

.. code-block:: sh

   $ n98-magerun.phar dev:class:lookup model catalog/product

Toggle Symlinks
"""""""""""""""

Allow usage of symlinks for a store-view:

.. code-block:: sh

   $ n98-magerun.phar dev:symlinks [--on] [--off] [--global] [store_code]

Global scope can be set by not permitting store_code parameter:

.. code-block:: sh

   $ n98-magerun.phar dev:symlinks

Create Module Skeleton
""""""""""""""""""

Creates an empty module and registers it in current Magento shop:

.. code-block:: sh

   $ n98-magerun.phar dev:module:create [--add-controllers] [--add-blocks] [--add-helpers] [--add-models] [--add-setup] [--add-all] [--modman] [--add-readme] [--add-composer] [--author-name[="..."]] [--author-email[="..."]] [--description[="..."]] vendorNamespace moduleName [codePool]

Code-Pool defaults to `local`.


Example:

.. code-block:: sh

   $ n98-magerun.phar dev:module:create MyVendor MyModule


* `--modman` option creates a new folder based on `vendorNamespace` and `moduleName` argument.
Run this command inside your `.modman` folder.

* --add-all option add blocks, helpers and models.

* --add-readme Adds a readme.md file to your module.

* --add-composer Adds a composer.json to your module.

* --author-email Author email for composer.json file.

* --author-name Author name for composer.json file.


.. code-block:: sh

   $ n98-magerun.phar dev:code:model:method [modelName]

Enable/Disable Module in Declaration
""""""""""""""""""""""""""""""""""""

Enable or disable a module in `app/etc/modules/*.xml` by name or codePool:

.. code-block:: sh

   $ n98-magerun.phar dev:module:enable [--codepool="..."] moduleName
   $ n98-magerun.phar dev:module:disable [--codepool="..."] moduleName

Examples:

.. code-block:: sh

   $ n98-magerun.phar dev:module:disable MyVendor_MyModule
   $ n98-magerun.phar dev:module:disable --codepool="community"


.. hint::

   If `--codepool` option is specified all modules in the codepool are affected.

List Modules
""""""""""""

Lists all installed modules with codepool and version

.. code-block:: sh

   $ n98-magerun.phar dev:module:list  [--codepool[="..."]] [--status[="..."]] [--vendor=[="..."]] [--format[="..."]]

Rewrite List
""""""""""""

Lists all registered class rewrites.

.. code-block:: sh

   $ n98-magerun.phar dev:module:rewrite:list [--format[="..."]]

Rewrite Conflicts
"""""""""""""""""

Lists all duplicated rewrites and tells you which class is loaded by Magento.
The command checks class inheritance in order of your module dependencies.

.. code-block:: sh

   $ n98-magerun.phar dev:module:rewrite:conflicts [--log-junit="..."]

* If a filename with `--log-junit` option is set the tool generates an XML file and no output to *stdout*.

Module Dependencies
"""""""""""""""""""

Show list of modules which given module depends on

.. code-block:: sh

   $ n98-magerun.phar dev:module:dependencies:on [-a|--all] [--format[="..."]] moduleName

Show list of modules which depend from module

.. code-block:: sh

   $ n98-magerun.phar dev:module:dependencies:from [-a|--all] [--format[="..."]] moduleName

Observer List
"""""""""""""

Lists all registered observer by type.

.. code-block:: sh

   $ n98-magerun.phar dev:module:observer:list [type]

Type is one of "adminhtml", "global", "frontend".

Theme List
""""""""""

Lists all frontend themes

.. code-block:: sh

   $ n98-magerun.phar dev:theme:list [--format[="..."]]


Find Duplicates in your theme
"""""""""""""""""""""""""""""

Find duplicate files (templates, layout, locale, etc.) between two themes.

.. code-block:: sh

   $ n98-magerun.phar dev:theme:duplicates [--log-junit="..."] theme [originalTheme]

* `originTheme` default is "base/default".

Example:

.. code-block:: sh

   $ n98-magerun.phar dev:theme:duplicates default/default


* If a filename with `--log-junit` option is set the tool generates an XML file and no output to *stdout*.

Create dummy Category
"""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar category:create:dummy

Create dummy categories with all default vanilla magento or your custom values.

**Interactive mode** or via **shell arguments** or mixed.

+------------------------------+---------------------------------------------------------------------------------------------+--------------------------------------------------+
| Arguments                    | Description                                                                                 | Accepted Values                                  |
+------------------------------+---------------------------------------------------------------------------------------------+--------------------------------------------------+
| `store-id`                   | Id of Store to create categories (default: 1)                                               | only integer                                     |
+------------------------------+---------------------------------------------------------------------------------------------+--------------------------------------------------+
| `category-number`            | Number of categories to create (default: 1)                                                 | only integer                                     |
+------------------------------+---------------------------------------------------------------------------------------------+--------------------------------------------------+
| `children-categories-number` | Number of children for each category created (default: 0 - use '-1' for random from 0 to 5) | only integer or -1 for random number from 0 to 5 |
+------------------------------+---------------------------------------------------------------------------------------------+--------------------------------------------------+
| `category-name-prefix`       | Category Name Prefix (default: 'My Awesome Category')                                       | any                                              |
+------------------------------+---------------------------------------------------------------------------------------------+--------------------------------------------------+

Create dummy Dropdown Attribute Values
""""""""""""""""""""""""""""""""""""""

.. code-block:: sh

   $ n98-magerun.phar eav:attribute:create-dummy-values

Create dummy attribute values (ONLY FOR DROPDOWN ATTRIBUTE)

**Interactive mode** or via **shell arguments** or mixed.

+------------------------------+----------------------------------------------+--------------------------------------------------------------+
| Arguments                    | Description                                  | Accepted Values                                              |
+------------------------------+----------------------------------------------+--------------------------------------------------------------+
| `locale`                     | Locale value in ISO standard like en_US      | only string                                                  |
+------------------------------+----------------------------------------------+--------------------------------------------------------------+
| `attribute-id`               | Attribute ID to add values                   | only integer                                                 |
+------------------------------+----------------------------------------------+--------------------------------------------------------------+
| `values-type`                | Types of Values to create (default int)      | `int`<br />`string`<br />`color`<br />`size`<br />`designer` |
+------------------------------+----------------------------------------------+--------------------------------------------------------------+
| `values-number`              | Number of Values to create (default 1)       | only integer                                                 |
+------------------------------+----------------------------------------------+--------------------------------------------------------------+

List Extensions
"""""""""""""""

List and find connect extensions by a optional search string:

.. code-block:: sh

   $ n98-magerun.phar extension:list [--format[="..."]] <search>

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

Magento Installer
"""""""""""""""""

Since version 1.1.0 we deliver a Magento installer which does the following:

* Downloads Magento by a list of git repos and zip files (mageplus, magelte, official community packages).
* Tries to create database if it does not exist.
* Installs Magento sample data if available (since version 1.2.0).
* Starts Magento installer
* Sets rewrite base in .htaccess file

Interactive installer:

.. code-block:: sh

   $ n98-magerun.phar install

Unattended installation:

.. code-block:: sh

   $ n98-magerun.phar install [--magentoVersion[="..."]] [--magentoVersionByName[="..."]] [--installationFolder[="..."]] [--dbHost[="..."]] [--dbUser[="..."]] [--dbPass[="..."]] [--dbName[="..."]] [--installSampleData[="..."]] [--useDefaultConfigParams[="..."]] [--baseUrl[="..."]] [--replaceHtaccessFile[="..."]]

Example of an unattended Magento CE 1.7.0.2 installation:

.. code-block:: sh

   $ n98-magerun.phar install --dbHost="localhost" --dbUser="mydbuser" --dbPass="mysecret" --dbName="magentodb" --installSampleData=yes --useDefaultConfigParams=yes --magentoVersionByName="magento-ce-1.7.0.2" --installationFolder="magento" --baseUrl="http://magento.localdomain/"

Additionally, with --noDownload option you can install Magento working copy already stored in --installationFolder on
the given database.

See it in action: http://youtu.be/WU-CbJ86eQc


Magento Uninstaller
"""""""""""""""""""

Uninstalls Magento: Drops your database and recursive deletes installation folder.

.. code-block:: sh

   $ n98-magerun.phar uninstall [-f|--force] [--installationFolder[="..."]]

**Please be careful: This removes all data from your installation.**

--installationFolder is required and if you do not enter it you will be prompted for it. This should be your project
root, not the Magento root. For example, If your project root is /var/www/site and Magento src is located at
/var/www/site/htdocs, you should pass /var/www/site to the command, or if you are currently in that particular directory
you can just pass "." Eg:

.. code-block:: sh

   $ cd /var/www/site
   $ n98-magerun.phar uninstall --installationFolder "." -f

If you omit the -f, you will be prompted for confirmation.

n98-magerun Shell
"""""""""""""""""

If you need autocompletion for all n98-magerun commands you can start with "shell command".

.. code-block:: sh

   $ n98-magerun.phar shell

n98-magerun Script
""""""""""""""""""

Run multiple commands from a script file.

.. code-block:: sh

   $ n98-magerun.phar script [-d|--define[="..."]] [--stop-on-error] [filename]

Example:

.. code-block::

   # Set multiple config
   config:set "web/cookie/cookie_domain" example.com

   # Set with multiline values with "\n"
   config:set "general/store_information/address" "First line\nSecond line\nThird line"

   # This is a comment
   cache:flush


Optionally you can work with unix pipes.

.. code-block:: sh

   $ echo "cache:flush" | n98-magerun-dev script

.. code-block:: sh

   $ n98-magerun.phar script < filename

It is even possible to create executable scripts:

Create file `test.magerun` and make it executable (`chmod +x test.magerun`):

.. code-block:: sh

   #!/usr/bin/env n98-magerun.phar script

   config:set "web/cookie/cookie_domain" example.com
   cache:flush

   # Run a shell script with "!" as first char
   ! ls -l

   # Register your own variable (only key = value currently supported)
   ${my.var}=bar

   # Let magerun ask for variable value - add a question mark
   ${my.var}=?

   ! echo ${my.var}

   # Use resolved variables from n98-magerun in shell commands
   ! ls -l ${magento.root}/code/local

Pre-defined variables:

* ${magento.root}    -> Magento Root-Folder
* ${magento.version} -> Magento Version i.e. 1.7.0.2
* ${magento.edition} -> Magento Edition -> Community or Enterprise
* ${magerun.version} -> Magerun version i.e. 1.66.0
* ${php.version}     -> PHP Version
* ${script.file}     -> Current script file path
* ${script.dir}      -> Current script file dir

Variables can be passed to a script with "--define (-d)" option.

Example:

.. code-block:: sh

   $ n98-magerun.phar script -d foo=bar filename

   # This will register the variable ${foo} with value bar.

It's possible to define multiple values by passing more than one option.


n98-magerun Script Repository
"""""""""""""""""""""""""""""
You can organize your scripts in a repository.
Simply place a script in folder */usr/local/share/n98-magerun/scripts* or in your home dir
in folder *<HOME>/.n98-magerun/scripts*.

Scripts must have the file extension *.magerun*.

After that you can list all scripts with the *script:repo:list* command.
The first line of the script can contain a comment (line prefixed with #) which will be displayed as description.

.. code-block:: sh

   $ n98-magerun.phar script:repo:list [--format[="..."]]

If you want to execute a script from the repository this can be done by *script:repo:run* command.

.. code-block:: sh

   $ n98-magerun.phar script:repo:run [-d|--define[="..."]] [--stop-on-error] [script]

Script argument is optional. If you don't specify any you can select one from a list.

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

Add your repo. The keys in the config file follow the composer package structure.

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

* Add new commands.
* Send me some proposals if you miss anything.
* Create issues if you find a bug or missing a feature.

Thanks to
---------

* Symfony2 Team for the great console component.
* Composer Team for the downloader backend and the self-update command.
* Francois Zaninotto for great Faker library.
