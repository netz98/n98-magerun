RECENT CHANGES
==============

1.97.29
-------
* Fix: Enterprise Edition detection / enabling commands (report by Luke Rodgers, fix by Tom Klingenberg, #902)
* Fix: Nonexistent class reference in config (by Tom Klingenberg)
* Fix: File integrity checking guide (by Max Chadwick, #896)
* Fix: Prevent sys:setup:run from ending script (report by Tjerk Ameel, #895)
* New: Add package magento-mirror-1.9.3.2 (by Peter O'Callaghan, #894)
* New: Streamlining of N98-Magerun1 and N98-Magerun2 (by Tom Klingenberg)

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
