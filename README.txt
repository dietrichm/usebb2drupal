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

A number of custom user fields are added, including 'occupation', 'interests',
etc. The migration fills in some user data in these fields. However, the fields
are not shown by default in the user (form) views. Enable them at:

  * admin/config/people/accounts/form-display
  * admin/config/people/accounts/display

Also, user signatures must eventually be enabled at
admin/config/people/accounts.


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

Q: What happens with BBCode syntax?

A: All BBCode is converted to HTML with a custom text format 'Forum HTML'.
   However, due to limitations in the format, color tags are stripped and size
   tags with at least 12pt are translated to h2 elements, leaving but one
   custom text size (for now).


MAINTAINERS
-----------

Current maintainers:

  * Dietrich Moerman (DietrichM) - https://www.drupal.org/u/dietrichm
