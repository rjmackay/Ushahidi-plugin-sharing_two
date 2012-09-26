=== About ===
name: Sharing 2.0
website: http://www.ushahidi.com
description: Share reports among deployments
version: 2.0
requires: 2.5
tested up to: 2.5
author: Ushahidi Team
author website: http://www.ushahidi.com

== Description ==
Share reports among deployments. A rebooted version of the core sharing plugin.
This plugin handles shared reports more like core reports:

* Added to the same layer on the map
* Visible in the reports listing
* Reports filterable by category
* Reports viewing in the current site, rather than always redirecting to the original site

Incompatible with: Sharing, Actionable

Config options:
* Default to showing all sites reports / showing this sites reports
* Show reports in this site / Redirect reports to origin site
* Ignore reports not in match category / Create missing categories
(not sure if we'll show category-less reports in 'all categories')

Sponsored by APC (built for takebackthetech.net)

== Installation ==
1. Make sure the ushahidiapilibrary plugin is present and installed
2. Copy the entire /sharing_two/ directory into your /plugins/ directory.
3. Activate the plugin.
4. If you have a custom theme
  a. Copy views/reports/detail.php to your theme
  b. Copy views/reports/list.php to your theme
5. Copy views/admin/comments/main.php in applications/views

