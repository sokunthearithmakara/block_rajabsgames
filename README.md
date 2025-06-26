# Rajab\'s games #

Rajab\'s games is a Moodle block plugin that allows you to gamify your course with interactive videos.

## Features ##

* Participant leaderboard: displays top 5 participants based on their XP.
* Group leaderboard: displays groups and their total XP.
* Badges: create badges that can be embedded in your videos and let participants earn them.
* Levels: create unlockable levels that participants can complete based on their XP.
* Group support: instructor can choose between visible and separate groups. In visible groups, participant leaderboard will be calculated from the whole course and group icon will be visible. They can also see XP progress of other groups. In separate groups, participant leaderboard will be calculated from the group only, and they will not see XP progress of other groups.


## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/blocks/rajabsgames

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2025 Your Name <you@example.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
