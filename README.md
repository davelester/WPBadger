# WPBadger

## Overview
WPBadger is a simple WordPress plugin for issuing badges and adding them to a user's [Open Badges](http://openbadges.org) backpack. Consider it [Open Badger](https://github.com/mozilla/OpenBadger/wiki)'s lighter-weight cousin.

## Installation

1. Download the WPBadger plugin, moving the WPBadger folder into the /wp-content/plugins/ directory on your server. (Note: in order for the plugin to work, it must be installed on a web-accessible server, and not a local machine) If downloading the plugin from github, click tags and make sure you download the latest version.

2. Install the WPBadger plugin on WordPress like any other plugin.

3. Configure the plugin by navigating to Settings -> WPBadger Config in the WordPress admin. On this form, fill out some basic information including Agent Name, organization, and contact email address. The award email text is optional.

## Instructions for Using WPBadger
1. Next, you need to add a badge. Click the "Badges" link on the left side of the WordPress admin, and add a new badge. The title is the name of your badge, the main textarea is where you describe your badge, there's a field for a badge version (just make it something numerical, like 1.0), and you can set a badge image. Note that Open Badges requires you use a PNG image, but you can use any png image as your badge that you like.

2. Once a badge has been added, you can "award" it to individuals. Click the "Awards" link on the left side of the WordPress admin, entering the reason an individual was awarded the badge in the main textarea, using the drop-down menu to select the specific badge, and entering their email. Upon awarding a badge, an email is sent to the user's email address notifying them of the award.

3. A user receiving an award can then click the link in their email, and on that page choose to accept or decline the badge they have been awarded. If they choose to accept, they are shown a lightbox for the Mozilla OpenBadges backpack, which manages the process of storing badges.

## Details
See the [WPBadger wiki](https://github.com/davelester/wpbadger/wiki) for details on the plugin's roadmap, a list of early adopters and examples, and contact information. If you run into a problem, share your problem on the [issue tracker](https://github.com/davelester/WPBadger/issues?state=open).