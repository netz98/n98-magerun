RECENT CHANGES
==============

1.97.23
-------
* Fix: Strip email queue tables for development db:dump command (by Robbie Averill, #836)
* Imp: Create controllers folder in dev:module:create command (by Alexander Turiak, #835)
* Imp: Update example to use config:search command (by Hardy Johnson, #834)
* Imp: Build in clean directory (by Tom Klingenberg)
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
