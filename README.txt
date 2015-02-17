INTRODUCTION
------------

UseBB2Drupal extends upon the core migrate module to provide migration from
legacy UseBB 1 forums to Drupal 8. It converts users, categories, forums,
topics and posts without overwriting existing Drupal content.


INSTALLATION
------------

Simply enable the module at the Extend page or use drush:

$ drush en usebb2drupal


CONFIGURATION
-------------

There is no configuration to be made, but the migration can be started from
admin/structure/forum/migrate-usebb. Specify UseBB's database info to start
converting.

Currently, the migration process has not been tested with drush, but should
work fine by specifying a manifest file as described at
https://www.drupal.org/node/2257723.


TROUBLESHOOTING
---------------

The migration batch does not show detailed error/warning messages. If a
migration fails, check the database error log at admin/reports/dblog. Please
report any problems in the project's issue tracker.


FAQ
---

Q: What UseBB data is (currently) not migrated?

A: User ranks, avatars, subscriptions, bad words, username/email/IP address
   bans, forum access permissions, moderator permissions, and smilies.
   Specific UseBB user and global board settings are not migrated, since not
   all UseBB functionality is being replicated in Drupal. Once more contributed
   modules are released, some of this functionality may become available with
   later versions of UseBB2Drupal.


MAINTAINERS
-----------

Current maintainers:

  * Dietrich Moerman (DietrichM) - https://www.drupal.org/u/dietrichm
