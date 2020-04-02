RECENT CHANGES
==============

1.103.3
-------

* Fix: Disable mview related commands in Magento CE (reported by, Ig0r-M #1051)

1.103.2
-------

* Imp: Change config init to Magento's reinit function (by Thomas Wiringa)
* Fix: Cache clean won't work if database user is wrong (reported by Izzulmakin, #1046)
* Fix: Wildcards in db:dump strip option (by Dan Wallis,#1042)
* Fix: invalid composer package name (by Christian Münch, #1043)
* Fix: Instances of PHP Fatal error: Uncaught ArgumentCountError (by Luke Rodgers, #1044)

1.103.1
-------

* Compatibility for PHP 7.3 (by Achim Rosenhagen)
* Adding OpenMage Magento LTS 1.9.4.x (by Sven Reichel)


1.103.0
-------

* New: Add connection option for db:dump command (by Igor Mursa)
* New: Removed support for PHP < 5.4
* Imp: Hide password in interactive mode (reported by Simon Sprankel)
* Imp: Phpstorm meta files compatibility for latest PhpStorm (by Sven Reichel)
* Fix: Updated twig (security)
* Fix: db:dump returns exit code 0 on fail (by Christian Münch)
* Fix: Documentation about script command (by Hardy Johnson)
* Fix: Readme formatting (by Danila Vershini)

1.102.0
-------

* New: Magento 1.9.3.10 (by Bono de Visser, #997)
* New: Magento 1.9.3.9 (by Joel Lupfer, #999)
* New: dry-run flag for self-update command (port of M2 project)
* Add openmage-lts to installable versions (by Daniel Fahlke, #1005)
* Fix: Avoid stripping newsletter templates when stripping subscribers (by Scott Buchanan, #993)
* Fix: Remove obsolete global DB env variabe (by Daniel Ruf, #988)
* Imp: Add database setting to travis test matrix (by Daniel Ruf, #986)

1.101.1
-------

* New: Magento 1.9.3.8 support for installer (by Brad Berger, #979)
* Fix: Execution of sys:cron:run (by Ricardo Velhote, #977)

1.101.0
-------

* Fix: Typo in config:get command (by Ari Molzer, #958)
* New: Meta generator for phpstorm > 2016.1 (by Guillaume Gill, #965)
* Imp: Option to schedule cron in magento crontab instead of running with current user (by Guillaume Gill, #966)
* New: Add current Magento version 1.9.3.7 (by Brad Berger, #967)
* Imp: Support for multi module decleration in module:enable command (by Peter O'Callaghan, #969)
* Imp: Ability to import db from STDIN (by Peter O'Callaghan, #970)
* Imp: Add Magento-Root to sys:info command (by Christian Münch)

1.100.0
-------

* New: db:dump - allow arbitrary mysqldump options (by Brice Burgess, #945)
* strip admin tables (by Max Chadwick, #946)
* New: Add cms:block:list command (by Luke Rodgers, #948)
* New: Add current Magento version 1.9.3.6 (by Simon Sprankel, #951)
* New: config for pre-commit framework (by Christian Münch)
* Fix: Codecov settings (by Christian Münch)
* Imp: Add yaml quoting (by Tom Klingenberg)

1.99.0
------
* Fix: Fatal error w/ Magento Composer Installer (by Luke Rodgers and Tom Klingenberg, #938)
* Remove PHP 5.3 after travis ended support (by Christian Münch, #941)
* Support for Magento developer mode (by Luke Rodgers, #940)

1.98.0
------
* Fix: Borken image Bitdeli.com badge in readme (by Tom Klingenberg)
* Fix: Check Suhosin phar support (report by Decorate, fix by Tom Klingenberg, #926)
* Fix: File hash is optional in package.xml (report by Viktor Szépe, fix by Tom Klingenberg, #927)
* Fix: Show full path for phpstorm autocompletion folder (by Carlos Reynosa, #922)
* Fix: Enterprise Edition detection / enabling commands (report by Luke Rodgers, fix by Tom Klingenberg, #902)
* Fix: Nonexistent class reference in config (by Tom Klingenberg)(by Chris Potter, #909)
* Fix: File integrity checking guide (by Max Chadwick, #896)
* Fix: Prevent sys:setup:run from ending script (report by Tjerk Ameel, #895)
* Fix: Update script command to have non-zero exit code (by Chris Potter, #908)
* Fix: Remove duplicate entry in config.yaml (by Alexander Menk, #914)
* Fix: Restore historic packages for install command (by Tom Klingenberg)
* Fix: Compatibility for installed Symfony 3 components (by Christian Münch)
* Fix: Update readme (by Goose)
* New: Add package magento-mirror-1.9.3.2 (by Peter O'Callaghan, #894)
* New: Add current Magento version 1.9.3.3 (by Jonas Hüning, #920)
* New: Add current Magento version 1.9.3.4 (by Will-B, #930)
* New: Check Suhosin phar support (by Tom Klingenberg, #926)
* New: New commands to clear media and css cache (by Christian Münch, #932)
* New: Streamlining of N98-Magerun1 and N98-Magerun2 (by Tom Klingenberg)

1.97.30
-------
* Fix: Corrected wrong package name in installation source

1.97.29
-------
* Fix: Install command fails (report by Eugen Wesseloh, #906)


1.97.28
-------
* Fix: sys:setup:run exists with code 0 on error (report by Matías Montes, fix by Tom Klingenberg, #854)
* Fix: URL generation in sys:cron:run (report by Ash Smith, fix by Tom Klingenberg, #871)
* Fix: Indexer dies on error (report by Henry Hirsch, fix by Tom Klingenberg, #701)
* Fix: Incompatibilities with PHP 7.1 (report by Don Bosco van Hoi, fix by Tom Klingenberg, #881)
* Fix: Warning db:import sprintf too few arguments (report by Peter Jaap Blaakmeer, fix by Tom Klingenberg, #884)
* Fix: Empty database hostname for mysql cli (report by Seansan, fix by Tom Klingenberg, #880)
* Imp: Build phar reproduceable and from dev requirements (by Tom Klingenberg)
* Imp: Support NULL values in config:set and config:get (by Tom Klingenberg)
* New: Add index:list:mview and index:reindex:mview commands (by Luke Rodgers, #891)
* New: Add --include parameter to db:dump command (by Jarod Hayes, #848)

1.97.27
-------
* Fix: Sourceforge moved to https (by Tom Klingenberg)
* Fix: Broken sample-data tar.gz file (report by AreDubya, #879)
* Fix: Self-check in fake phar (report by Liviu Panainte, thanks!)

1.97.26
-------
* Upd: bash autocomplete-file
* Imp: Hide password when asked by admin:user:change-password (report by Faisal Mirza, #873)

1.97.25
-------
* Fix: Add missing new commands to config.yaml (by Giuseppe Morelli, #877)

1.97.24
-------
* Fix: Array to string conversion notice (report by Christian Münch, fix by Tom Klingenberg)
* Fix: Endless download loop (report by Vinai Kopp, fix by Tom Klingenberg, #876)

1.97.23
-------
* Fix: Add missing sample-data package 1.9.2.4 (by Tom Klingenberg, #872)
* Fix: Set created_at in schedule when running a cron job (by Toon Spin,  #874)
* Fix: Print exceptions on cron run (by Luke Rodgers, #862)
* Fix: Prevent PHP fatal errors in dev:module:rewrite:conflicts (report by Simon Sprankel, fix by Tom Klingenberg, #856)
* Fix: Strip email queue tables for development db:dump command (by Robbie Averill, #836)
* Upd: Documentation patch (by Rafael Corrêa Gomes, #869)
* Imp: Create controllers folder in dev:module:create command (by Alexander Turiak, #835)
* Imp: Update example to use config:search command (by Hardy Johnson, #834)
* Imp: Build in clean directory (by Tom Klingenberg)
* New: Add --reinit and --no-reninit options for cache:clean and cache:flush (by arollason, #863)
* New: Add Magento CE 1.9.3.1 to config.yaml (by Tom Klingenberg, #872)
* New: Add Magento CE 1.9.3.0 to config.yaml (by Marty S/sylink, #867)
* New: Coding standard defintion and checks (by Tom Klingenberg)
* New: Add sys:setup:run --no-implicit-cache-flush option (report by Fabrizio Branca, fix by Tom Klingenberg, #850)
* New: Add eav:attribute:create-dummy-values command (by Giuseppe Morelli, #849)
* New: Add category:create:dummy command (by Giuseppe Morelli, #845)
* New: Add config classes streamlining with Magerun 2 (by Tom Klingenberg)
* New: Add model::method to sys:cron:list command (by Steve Robbins, #838)
* New: Integration test for db:dump (by Tom Klingenberg)
* New: Add Homebrew installation (by Matthéo Geoffray, #829)

1.97.22
-------
* Fix: Fix open command detection (by Tom Klingenberg)
* Fix: Add customer data stripping tables, fixes #825 (by Matthew O'Loughlin)
* Fix: Install option --forceUseDb has no value, fixes #822 (report by Adam Johnson, fix by Tom Klingenberg)

1.97.21
-------
* Fix: codePool header regression in 1.97.20 (report by Jeroen Boersma)

1.97.20
-------
* Fix ask store endless loop in non-interactive mode (by Tom Klingenberg)
* Fix unset index usage in dev:module:list (by Pieter Hoste)
* Support magic \__call calls for sys:cron:run (by Sam Tay)
* Fallback to .n98-magerun.yaml in homedir on windows (by Tom Klingenberg)
* Update MySQL Engine Check to handle incomplete installations (by Navarr Barnier, Tom Klingenberg)
* Feature: New dev:email-template:usage command (by Mike Parkin)
* Feature: Notify of developer IP on changing template hints; closes #23 (by Robbie Averill)
* Feature: Extract config-loader (by Tom Klingenberg)

1.97.19
-------
* Add config:delete and config:set --force option (by Robert Coleman)
* Add prompt when unlocking all admin users (by Ben Robie and Robbie Averill)
* Add admin:user:lock and lockdown commands (by Robbie Averill)

1.97.18
-------
* Upd: Stabilize composer ^1.0.0 (by Tom Klingenberg)
* Feature: Add dry-run mode for db:dump (by Tom Klingenberg)

1.97.17
-------
* Fix: Fix module loader (report by Matthias Walter, fix by Tom Klingenberg)
* Fix config:delete (by Tom Klingenberg, #805)

1.97.16
-------
* Fix plugin loader to use 'n98-magerun' folder (by Rouven Alexander Rieker, #800)

1.97.15
-------
* Add package magento-mirror-1.9.2.4 (by Raul E Watson, #798)
* Streamlining of N98-Magerun1 and N98-Magerun2 (by Tom Klingenberg, #797)

1.97.14
-------
* Fix crontab event observers / event area in sys:cron:run (by Tom Klingenberg, #794)
* Add .github pull request template (by Tom Klingenberg, #791)

1.97.13
-------
* Update readme with compare-versions changes (by Luke Rodgers, #789)

---

References
----------

* See the full change log: https://github.com/netz98/n98-magerun/wiki/Changelog
* Visit our blog: http://magerun.net
