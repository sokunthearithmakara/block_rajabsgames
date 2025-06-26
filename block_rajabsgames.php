<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use core_group\reportbuilder\datasource\groups;

/**
 * Block block_rajabsgames
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/blocks}
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_rajabsgames extends block_base {

    /**
     * Block initialisation
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_rajabsgames');
    }

    /**
     * Returns the formats where the block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => false, 'course' => true];
    }

    /**
     * Get content
     *
     * @return stdClass
     */
    public function get_content() {
        global $OUTPUT, $USER, $DB;
        $courseid = $this->page->course->id;
        $hasbadge = true;
        $haslevel = true;
        $hasleaderboard = true;

        if ($this->content !== null) {
            return $this->content;
        }

        if ($this->config == null) {
            $this->content = (object)[
                'text' => '<div class="d-flex align-items-center flex-column">'
                    . '<i class="fa fa-exclamation-triangle fa-5x mb-3"></i>'
                    . '<p class="text-muted">'  . get_string('confignotfound', 'block_rajabsgames') . '</p></div>',
            ];
            return $this->content;
        }

        // Get my XP: sum of xp from interactivevideo_completion table.
        $sql = 'SELECT SUM(xp) as myxp FROM {interactivevideo_completion}'
            . ' WHERE userid = :userid AND cmid IN (SELECT id FROM {interactivevideo} WHERE course = :courseid)';

        $params = [
            'userid' => $USER->id,
            'courseid' => $courseid,
        ];

        $myxp = $DB->get_record_sql($sql, $params, IGNORE_MISSING);

        // Get total XP: sum of xp from interactivevideo_items table.
        $sql = 'SELECT SUM(xp) as totalxp FROM {interactivevideo_items} WHERE courseid = :courseid';

        $params = [
            'courseid' => $courseid,
        ];

        $totalxp = $DB->get_record_sql($sql, $params, IGNORE_MISSING);

        if ($this->config->badges && ($this->config->showbadges == 1 || has_capability('block/rajabsgames:addinstance', $this->context))) {
            // Get badges in used.
            $usedbadges = $DB->get_records(
                'interactivevideo_items',
                [
                    'courseid' => $courseid,
                    'type' => 'rajabsgames',
                ],
                null,
                'id, content'
            );

            if (!$usedbadges && !has_capability('block/rajabsgames:addinstance', $this->context)) {
                // If not a teacher, don't show unused blocks.
                $badges = [];
            } else {
                $ids = [];
                foreach ($usedbadges as $ub) {
                    $ub = json_decode($ub->content, true);
                    foreach ($ub as $b) {
                        $ids[] = $b['badgeid'];
                    }
                }

                // My badges.
                $sql = 'SELECT completiondetails
                        FROM {interactivevideo_completion}
                        WHERE userid = :userid
                        AND cmid IN (SELECT id FROM {interactivevideo} WHERE course = :courseid)
                        AND completiondetails LIKE :progress';

                $completions = $DB->get_records_sql($sql, [
                    'userid' => $USER->id,
                    'courseid' => $courseid,
                    'progress' => '%gameprogress%',
                ], IGNORE_MISSING);

                if (!$completions) {
                    $completions = [];
                    $progress = [];
                } else {
                    $progress = [];
                    foreach ($completions as $completion) {
                        $completion = json_decode($completion->completiondetails, true);
                        $completion = array_filter($completion, function ($c) {
                            return strpos($c, 'gameprogress');
                        });
                        $completion = reset($completion);
                        $completion = json_decode($completion, true);
                        $prog = $completion['gameprogress'];
                        foreach ($prog as $pro) {
                            $progress[] = $pro['id'];
                        };
                    }
                }

                $badges = file_rewrite_pluginfile_urls(
                    $this->config->badges,
                    'pluginfile.php',
                    $this->context->id,
                    'block_rajabsgames',
                    'content',
                    null
                );
                $badges = json_decode($badges, true) ?? [];
                $badges = array_map(function ($badge) use ($ids, $progress) {
                    $badge = (object)$badge;
                    $badge->isblock = true;
                    // Mine.
                    $badge->mine = array_filter($progress, function ($p) use ($badge) {
                        return $p == $badge->id;
                    });
                    $badge->mine = count($badge->mine);
                    // Total.
                    $badge->badgecount = array_filter($ids, function ($id) use ($badge) {
                        return $id == $badge->id;
                    });
                    $badge->badgecount = count($badge->badgecount);
                    $badge->unused = false;
                    if ($badge->badgecount == 0 && !has_capability('block/rajabsgames:addinstance', $this->context)) {
                        $badge->unused = true;
                    }
                    $badge->classes = $badge->mine < $badge->badgecount || $badge->badgecount == 0 ? 'filter' : '';
                    return $badge;
                }, $badges);
            }
        } else {
            $badges = [];
            $hasbadge = false;
        }

        if ($this->config->levels && ($this->config->showlevels == 1 || has_capability('block/rajabsgames:addinstance', $this->context))) {
            $levels = json_decode($this->config->levels, true) ?? [];
        } else {
            $levels = [];
            $haslevel = false;
        }

        // Map completed levels based on XP.
        $levels = array_map(function ($level) use ($myxp) {
            $level = (object)$level;
            $level->completed = $level->xp <= $myxp->myxp;
            $level->isblock = true;
            return $level;
        }, $levels);

        $mylevel = 0;
        for ($i = 0; $i < count($levels); $i++) {
            if ($levels[$i]->completed) {
                $mylevel = $levels[$i]->order;
            }
        }

        $levels = array_map(function ($level) use ($mylevel) {
            $level->current = $level->order == $mylevel + 1;
            return $level;
        }, $levels);

        $datafortemplate = (array)[
            'mypicture' => $OUTPUT->user_picture($USER, ['size' => 1]),
            'myname' => $USER->firstname,
            'myxp' => $myxp->myxp ?? 0,
            'mylevel' => $mylevel,
            'totalxp' => $totalxp->totalxp ?? 0,
            'totallevel' => count($levels),
            'xppercentage' => ($totalxp->totalxp == 0 ? 0 : $myxp->myxp / $totalxp->totalxp * 100),
            'levelpercentage' => (count($levels) == 0 ? 0 : $mylevel / count($levels) * 100),
            'badges' => $badges,
            'levels' => $levels,
        ];

        if ($datafortemplate['xppercentage'] == 100) {
            $datafortemplate['xpcompleted'] = true;
        }

        if ($datafortemplate['levelpercentage'] == 100) {
            $datafortemplate['levelcompleted'] = true;
        }

        // Grouping.
        $groupmode = $this->config->groupmode ?? 0;
        $grouping = $this->config->grouping ?? 0;
        $groups = [];
        $mygroups = [];
        $groupids = [];
        $mygroupids = [];
        if ($groupmode != 0 && $grouping != 0) {
            $groups = groups_get_all_groups($courseid, 0, $grouping, 'g.*', false, true);
            $groups = array_map(function ($group) use ($courseid, $OUTPUT, $USER) {
                // Get picture if $group->picture is not 0.
                // If not hiding the group picture, and the group has a picture then use it. Fallback to generic group image.
                if ($url = get_group_picture_url($group, $courseid, true)) {
                    $group->pictureurl = $url->out();
                } else {
                    $group->pictureurl = $OUTPUT->image_url('g/g1')->out(false);
                }
                return (object)[
                    'name' => $group->name,
                    'pictureurl' => $group->pictureurl,
                    'id' => $group->id,
                    'mine' => groups_is_member($group->id, $USER->id),
                ];
            }, $groups);

            $mygroups = array_filter($groups, function ($group) {
                return $group->mine;
            });

            $groupids = array_map(function ($group) {
                return $group->id;
            }, $groups);

            $mygroupids = array_map(function ($group) {
                return $group->id;
            }, $mygroups);
        }

        if ($groupmode == VISIBLEGROUPS && $grouping != 0 && $this->config->showgroupleaderboard == 1) {
            $hasgroupleaderboard = true;
            $sql = 'SELECT gm.groupid, SUM(c.xp) as totalxp FROM {groups_members} gm
                JOIN {interactivevideo_completion} c ON gm.userid = c.userid
                WHERE gm.groupid IN (' . implode(',', $groupids) . ')
                AND c.cmid IN (SELECT id FROM {interactivevideo} WHERE course = :courseid)
                GROUP BY gm.groupid';
            $params = [
                'courseid' => $courseid,
            ];
            $gleaderboards = $DB->get_records_sql($sql, $params, IGNORE_MISSING);
            // Map totalxp and mine to the groups.
            $groups = array_map(function ($group) use ($gleaderboards, $groupids, $mygroupids) {
                $gleaderboard = array_filter($gleaderboards, function ($gleaderboard) use ($group) {
                    return $gleaderboard->groupid == $group->id;
                });
                if (empty($gleaderboard)) {
                    $group->totalxp = 0;
                    return $group;
                }
                $gleaderboard = reset($gleaderboard);
                $group->totalxp = $gleaderboard->totalxp;
                return $group;
            }, $groups);

            // Order the groups by totalxp.
            $groups = array_values($groups);
            usort($groups, function ($a, $b) {
                return $b->totalxp - $a->totalxp;
            });

            // Add rank.
            $i = 0;
            foreach ($groups as $group) {
                $group->rank = $i + 1;
                $i++;
            };

            $datafortemplate['gleaderboard'] = $groups;
        } else {
            $hasgroupleaderboard = false;
        }

        // Leaderboard: select top 5 users by total xp from interactivevideo_completion table in the course.
        if ($this->config->showleaderboard == 1 || has_capability('block/rajabsgames:addinstance', $this->context)) {
            $userfields = \core_user\fields::for_userpic()->with_name()->excluding('id');
            $userfields = $userfields->get_sql('u');
            $userfields = $userfields->selects;
            if ($groupmode == NOGROUPS) {
                $sql = 'SELECT u.id' . $userfields
                    . ', SUM(c.xp) as totalxp FROM {user} u JOIN {interactivevideo_completion} c ON u.id = c.userid'
                    . ' WHERE c.cmid IN (SELECT id FROM {interactivevideo} WHERE course = :courseid)'
                    . ' GROUP BY u.id ORDER BY totalxp DESC LIMIT 5';
            } else if ($groupmode == VISIBLEGROUPS) {
                // In visible groups, we need to get the total xp from all groups.
                $sql = 'SELECT u.id' . $userfields
                    . ', SUM(c.xp) as totalxp FROM {user} u JOIN {interactivevideo_completion} c ON u.id = c.userid'
                    . ' WHERE c.cmid IN (SELECT id FROM {interactivevideo} WHERE course = :courseid)'
                    . ' AND u.id IN (SELECT gm.userid FROM {groups_members} gm WHERE gm.groupid IN (' . implode(',', $groupids) . '))'
                    . ' GROUP BY u.id ORDER BY totalxp DESC LIMIT 5';
            } else if ($groupmode == SEPARATEGROUPS) {
                // In separate groups, we need to get the total xp from the mygroups only.
                $sql = 'SELECT u.id' . $userfields
                    . ', SUM(c.xp) as totalxp FROM {user} u JOIN {interactivevideo_completion} c ON u.id = c.userid'
                    . ' WHERE c.cmid IN (SELECT id FROM {interactivevideo} WHERE course = :courseid)'
                    . ' AND u.id IN (SELECT gm.userid FROM {groups_members} gm WHERE gm.groupid IN (' . implode(',', $mygroupids) . '))'
                    . ' GROUP BY u.id ORDER BY totalxp DESC LIMIT 5';
            }

            $groupids = implode(',', array_map(function ($group) {
                return $group->id;
            }, array_values($groups))); // Get the group ids.
            $mygroupids = implode(',', array_map(function ($group) {
                return $group->id;
            }, $mygroups)); // Get the group ids.

            $params = [
                'courseid' => $courseid,
                'groupids' => $groupids, // Get the group ids.
                'mygroupids' => $mygroupids, // Get the group ids.
            ];

            $tops = $DB->get_records_sql($sql, $params, IGNORE_MISSING);

            if (!$tops) {
                $tops = [];
            }

            // Map to assign rank, xp, level.
            $tops = array_map(function ($top) use ($levels, $OUTPUT, $USER, $groupmode, $grouping, $courseid, $groups, $mygroups) {
                $top = (object)$top;
                $xp = $top->totalxp;
                $level = 1;
                $levelname = '';
                foreach ($levels as $lvl) {
                    if ($lvl->order == 1) {
                        $levelname = $lvl->name;
                    }

                    if ($xp >= $lvl->xp) {
                        $level = $lvl->order;
                        $levelname = $lvl->name;
                    }
                }
                $top->level = $level;
                $top->levelname = $levelname;
                $top->xp = $xp;
                $top->picture = $OUTPUT->user_picture($top, ['size' => 1]);
                $top->name = $top->firstname;
                if ($groupmode != NOGROUPS && $grouping != 0) {
                    if ($groupmode == SEPARATEGROUPS) {
                        $theirgroups = [];
                    } else {
                        $theirgroups = array_filter($groups, function ($group) use ($top) {
                            return groups_is_member($group->id, $top->id);
                        });
                    }
                    $theirgroups = array_values($theirgroups);
                    $top->theirgroups = array_map(function ($group) {
                        return [
                            'gname' => $group->name,
                            'gpictureurl' => $group->pictureurl,
                            'gid' => $group->id,
                        ];
                    }, $theirgroups);
                }
                if ($top->id == $USER->id) {
                    $top->isuser = true;
                }
                return $top;
            }, $tops);

            $tops = array_values($tops);

            // Remove ones where totalxp is 0.
            $tops = array_filter($tops, function ($top) {
                return $top->totalxp > 0;
            });

            for ($i = 0; $i < count($tops); $i++) {
                // Account for xp that are the same. In which case, rank is the same.
                if ($i > 0 && $tops[$i]->totalxp == $tops[$i - 1]->totalxp) {
                    $tops[$i]->rank = $tops[$i - 1]->rank;
                } else {
                    $tops[$i]->rank = $i + 1;
                }
            }

            $datafortemplate['topthree'] = $tops;
            if (count($tops) == 0) {
                $hasleaderboard = false;
            }
        } else {
            $hasleaderboard = false;
        }

        if (has_capability('block/rajabsgames:addinstance', $this->context)) {
            $hasbadge = true;
            $haslevel = true;
            $hasleaderboard = true;
        }

        $datafortemplate['hasbadge'] = $hasbadge;
        $datafortemplate['haslevel'] = $haslevel;
        $datafortemplate['hasleaderboard'] = $hasleaderboard;
        $datafortemplate['hasgroupleaderboard'] = $hasgroupleaderboard;
        if ($groupmode != 0 && $grouping != 0) {
            $datafortemplate['mygroups'] = array_values($mygroups);
        }

        $this->content = (object)[
            'text' => $OUTPUT->render_from_template(
                'block_rajabsgames/main',
                $datafortemplate
            ),
        ];
        return $this->content;
    }

    /**
     * Delete the block instance.
     *
     * @return bool
     */
    public function instance_delete() {
        global $DB;
        // Delete all files.
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_rajabsgames');
        // Get rajabsgames items from interactivevideo_items.
        $items = $DB->get_records('interactivevideo_items', [
            'courseid' => $this->page->course->id,
            'type' => 'rajabsgames',
        ], null, 'annotationid');
        if ($items) {
            $cache = cache::make('mod_interactivevideo', 'iv_items_by_cmid');
            foreach ($items as $item) {
                // Delete cache for rebuild.
                $cmid = $item->annotationid;
                $cache->delete($cmid);
            }
        }
        // Delete the rajabsgames items from interactivevideo_items.
        $DB->delete_records('interactivevideo_items', [
            'courseid' => $this->page->course->id,
            'type' => 'rajabsgames',
        ]);
        // Clear the cache.
        $cache = \cache::make('block_rajabsgames', 'rajabsgames_configs');
        $cache->delete($this->page->course->id);
        parent::instance_delete();
        return true;
    }

    /**
     * Hide the block header.
     *
     * @return bool
     */
    public function hide_header() {
        return true;
    }

    /**
     * Loads the required JavaScript for the block.
     *
     * @return void
     */
    public function get_required_javascript() {
        parent::get_required_javascript();

        global $USER;
        // Add the main JavaScript file for the block.
        $this->page->requires->js_call_amd('block_rajabsgames/block_main', 'init', [
            'blockid' => $this->instance->id,
            'contextid' => $this->context->id,
            'courseid' => $this->page->course->id,
            'userid' => $USER->id,
        ]);
    }

    /**
     * Serialize and store config data
     */
    public function instance_config_save($data, $nolongerused = false) {
        $config = clone ($data);
        // Move embedded files into a proper filearea and adjust HTML links to match.
        $config->badges = file_save_draft_area_files(
            $data->draftid,
            $this->context->id,
            'block_rajabsgames',
            'content',
            0,
            ['subdirs' => true],
            $data->badges
        );

        $cachedata = [
            'badges' => file_rewrite_pluginfile_urls(
                $config->badges,
                'pluginfile.php',
                $this->context->id,
                'block_rajabsgames',
                'content',
                null
            ),
            'levels' => $config->levels,
            'id' => $this->instance->id,
        ];

        $cache = cache::make('block_rajabsgames', 'rajabsgames_configs');
        $cache->set($this->page->course->id, $cachedata);

        parent::instance_config_save($config, $nolongerused);
    }
}
