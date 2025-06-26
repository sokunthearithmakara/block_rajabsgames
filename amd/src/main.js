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

/**
 * TODO describe module main
 *
 * @module     block_rajabsgames/main
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import Base from 'mod_interactivevideo/type/base';
import DynamicForm from 'core_form/dynamicform';
import Template from 'core/templates';
import {renderAnnotationItems} from 'mod_interactivevideo/viewannotation';

export default class RajabsGames extends Base {
    /**
     * Initializes the decision plugin for interactive videos.
     *
     * This method sets up event listeners and handles the logic for decision points
     * within the video. If the video is not in edit mode, it filters out decision
     * annotations and prevents skipping certain decision points based on their
     * properties.
     *
     * @method init
     */
    async init() {
        let self = this;

        if (!this.isEditMode() && !this.isPreviewMode()) {
            const games = this.annotations.find(x => x.type == 'rajabsgames');
            if (!games) {
                return;
            }

            // Get the config data.
            let block = await $.ajax({
                url: M.cfg.wwwroot + '/blocks/rajabsgames/ajax.php',
                method: "POST",
                dataType: "text",
                data: {
                    action: 'get_badges',
                    sesskey: M.cfg.sesskey,
                    courseid: self.course,
                }
            });

            let badges = games.content;
            badges = JSON.parse(badges || '[]');

            let progress = await $.ajax({
                url: M.cfg.wwwroot + '/blocks/rajabsgames/ajax.php',
                method: "POST",
                dataType: "text",
                data: {
                    action: 'get_completiondetails',
                    sesskey: M.cfg.sesskey,
                    courseid: self.course,
                    cmid: self.interaction,
                    userid: self.userid
                }
            });
            progress = JSON.parse(progress || '[]');
            progress = JSON.parse(progress || '[]');
            progress = progress.map(x => JSON.parse(x));
            progress = progress.find(x => x.id == games.id);
            progress = progress ? progress.gameprogress || [] : [];

            if (badges.length == 0) {
                return;
            }

            let badgeconfig = JSON.parse(block);
            badgeconfig = JSON.parse(badgeconfig || '[]');

            badges = badges.map(badge => {
                badge.isblock = true;
                badge.unused = false;
                let badgedata = badgeconfig.find(x => x.id == badge.badgeid);
                if (badgedata) {
                    badge.name = badgedata.name;
                    badge.picture = badgedata.picture;
                    badge.id = badgedata.id;
                } else {
                    badge.id = 0;
                }
                if (progress.find(x => x.name == badge.uniqueid)) {
                    badge.completed = true;
                }
                return badge;
            });

            // Remove the badges that are no longer available in the course.
            badges = badges.filter(x => x.id != 0);

            // Badges for status: unique badges based on id.
            if (games.intg2 == 1) {
                let badgesforstatus = [];
                badges.forEach(badge => {
                    badge = JSON.parse(JSON.stringify(badge));
                    if (!badgesforstatus.find(x => x.id == badge.id)) {
                        badgesforstatus.push(badge);
                    }
                });

                badgesforstatus = badgesforstatus.map(badge => {
                    badge.mine = progress.filter(x => x.id == badge.id).length;
                    badge.badgecount = badges.filter(x => x.id == badge.id).length;
                    badge.completed = badge.mine == badge.badgecount;
                    badge.classes = badge.mine < badge.badgecount || badge.badgecount == 0 ? 'filter' : '';
                    return badge;
                });

                let status = await Template.render('block_rajabsgames/status', {
                    badges: badgesforstatus,
                });

                $('.navbar .navigation').prepend(status);
                $('[data-region="chapterwrapper"] > div').append(status);
            }

            // Check progress for non-existing badges and remove them.
            progress = progress.filter(x => badges.find(y => y.uniqueid == x.name));

            let cummulatedXP = 0;
            progress.forEach(x => {
                cummulatedXP += Number(x.xp);
            });

            let incompletedBadges = badges.filter(x => !x.completed);

            if (incompletedBadges.length == 0) {
                return;
            }

            let badgeHTML = await Template.render('block_rajabsgames/badge', {
                badges: incompletedBadges,
            });

            // Put the html to each badge.
            let $badgeHTML = $('<div>' + badgeHTML + '</div>');
            incompletedBadges = incompletedBadges.map(x => {
                let $el = $badgeHTML.find(`[data-id="${x.id}"]`);
                $el = $el.clone();
                $el.addClass('pulse-sm jellyIn');
                $el.attr('data-name', x.uniqueid);
                $el.attr('data-xp', x.xp);
                if (x.xp > 0) {
                    $el.append(`<small class="text-muted">
                        ${x.xp} <sup>${M.util.get_string('xp', 'block_rajabsgames')}</sup></small>`);
                }
                x.html = $el.prop('outerHTML');
                x.classes = self.getPosition(x.position);
                return x;
            });

            $(document).off('timeupdate.games').on('timeupdate.games', async function(e) {
                const t = Number(e.originalEvent.detail.time);
                const badge = incompletedBadges.find(x => x.timestamp <= t && x.timestamp + 5 >= t && !x.completed);
                let isEmpty = $('.video-block').find('li').length == 0;
                if (!badge && !isEmpty) {
                    $('.video-block').empty();
                    // Remove all classes.
                    $('.video-block').attr('class', 'video-block');
                    return;
                }
                if ($(`.video-block li`).length > 0) {
                    return;
                }
                // If there is a matching badge on timestamp, show it.
                if (badge) {
                    $('.video-block').empty();
                    // Remove all classes.
                    $('.video-block').attr('class', 'video-block');
                    $('.video-block').addClass(badge.classes + ' p-2');
                    $('.video-block').append(badge.html);
                    if (games.intg3 == 1) {
                        // Pause the video.
                        self.player.pause();
                    }
                }
            });

            // Claim the badge.
            $(document).off('click', '.video-block li').on('click', '.video-block li', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                // Update the status bar.
                if (games.intg2 == 1) {
                    let bdg = $(document).find('.rg_badgestatus_item[data-id="' + $(this).attr('data-id') + '"]');
                    if (bdg.length > 0) {
                        let count = bdg.find('.badge-count');
                        count.attr('data-count', Number(count.attr('data-count')) + 1);
                        if (count.attr('data-count') == count.attr('data-total')) {
                            bdg.find('img').removeClass('filter');
                        }
                        bdg.find('img').addClass('jellyIn');
                        setTimeout(() => {
                            bdg.find('img').removeClass('jellyIn');
                        }, 300);
                        count.text(count.attr('data-count'));
                    }
                }

                // Fire the confetti.
                window.fireConfetti();

                $(this).removeClass('jellyIn').addClass('jellyIn').fadeOut(300, () => {
                    $(this).remove();
                });

                // Update the progress to database.
                let details = {};
                const completeTime = new Date();
                let windowAnno = window.ANNOS.find(x => x.id == games.id);
                details.xp = cummulatedXP + Number($(this).data('xp'));
                cummulatedXP = details.xp;
                details.duration = windowAnno.duration + (completeTime.getTime() - windowAnno.newstarttime);
                details.timecompleted = completeTime.getTime();
                details.hasDetails = false;
                progress.push({
                    id: $(this).data('id'),
                    xp: $(this).data('xp'),
                    name: $(this).data('name'),
                });

                details.gameprogress = progress;
                details.completed = details.xp == games.xp;
                self.toggleCompletion(games.id, 'automatic', details);
                self.addNotification(M.util.get_string('xpearned', 'mod_interactivevideo', Number($(this).data('xp'))), 'success');

                // Remove the badge from the list.
                incompletedBadges = incompletedBadges.filter(x => x.uniqueid != $(this).data('name'));
                if (incompletedBadges.length == 0) {
                    // Stop the timeupdate.games event.
                    $(document).off('timeupdate.games');
                }
            });

        }
    }

    /**
     * Toggle completion of an item
     * @param {number} id The annotation id
     * @param {string} type The type of completion (manual, automatic)
     * @param {{}} [details={}] Completion details
     * @returns {Promise}
     */
    toggleCompletion(id, type = 'automatic', details = {}) {
        let self = this;
        // Skip if the page is the interactions page or in preview-mode.
        if (self.isEditMode()) {
            return Promise.resolve(); // Return a resolved promise for consistency
        }
        if (self.isPreviewMode()) {
            self.addNotification(M.util.get_string('completionnotrecordedinpreviewmode', 'mod_interactivevideo'));
            return Promise.resolve(); // Return a resolved promise for consistency
        }

        // Get existing progress so far.
        const gradableitems = self.annotations.filter(x => x.hascompletion == '1');
        const totalXp = gradableitems.map(({xp}) => Number(xp)).reduce((a, b) => a + b, 0);
        let completedItems = gradableitems.filter(({completed}) => completed);
        let earnedXp = completedItems.map(({earned}) => Number(earned)).reduce((a, b) => a + b, 0);

        completedItems = completedItems.map(({id}) => id);

        // Prepare details.
        let thisItem = gradableitems.find(({id: itemId}) => itemId == id);
        let completionDetails = {
            id,
        };
        const completeTime = new Date();
        completionDetails.hasDetails = details.details ? true : false;
        if (details.hasDetails) {
            completionDetails.hasDetails = true;
        }
        completionDetails.xp = details.xp || thisItem.xp;
        completionDetails.timecompleted = details.timecompleted || completeTime.getTime();
        const completiontime = completeTime.toLocaleString();
        completionDetails.gameprogress = details.gameprogress || [];
        completionDetails.reportView = details.reportView ||
            `<span data${self.isBS5 ? '-bs' : ''}-toggle="tooltip" data${self.isBS5 ? '-bs' : ''}-html="true"
                 title='<span class="d-flex flex-column align-items-start"><span><i class="bi bi-calendar iv-mr-2"></i>
                 ${completiontime}</span></span>'>
                 <i class="fa fa-${details.completed ? 'check text-success' : 'circle-o text-secondary'} "></i><br>
                 <span>${Number(completionDetails.xp)}</span></span>`;
        if (details.completed) {
            completedItems.push(id.toString());
        }
        if (thisItem.earned > 0) { // In case of resubmission.
            // Remove the earned XP from the total XP.
            earnedXp -= Number(thisItem.earned);
        }
        earnedXp += Number(completionDetails.xp);

        // Make sure the completed items are unique.
        completedItems = [...new Set(completedItems)];

        let completed;
        if (Number(self.completionpercentage) > 0) { // Completion percentage is set.
            completed = (completedItems.length / gradableitems.length) * 100 >= Number(self.completionpercentage) ? 1 : 0;
        } else {
            completed = gradableitems.length == completedItems.length ? 1 : 0;
        }
        return new Promise((resolve) => {
            $.ajax({
                url: `${M.cfg.wwwroot}/mod/interactivevideo/ajax.php`,
                method: "POST",
                dataType: "text",
                data: {
                    action: 'save_progress',
                    markdone: 'mark-done',
                    sesskey: M.cfg.sesskey,
                    id: self.interaction,
                    uid: self.userid,
                    percentage: (completedItems.length / gradableitems.length) * 100,
                    g: parseFloat((earnedXp / totalXp) * self.grademax).toFixed(2),
                    gradeiteminstance: self.gradeiteminstance,
                    c: completed,
                    xp: earnedXp,
                    completeditems: JSON.stringify(completedItems),
                    completiondetails: JSON.stringify(completionDetails),
                    details: JSON.stringify(details.details || {}),
                    annotationtype: thisItem.type,
                    token: self.token,
                    cmid: self.cm,
                    completionid: self.completionid,
                    contextid: thisItem.contextid,
                    updatestate: self.completionpercentage > 0 || Object.keys(self.extracompletion).length != 0 ? 1 : 0,
                    courseid: self.course,
                },
                success: (res) => {
                    // Update the annotations array.
                    const annotations = self.annotations.map(x => {
                        if (x.id == id) {
                            x.completed = details.completed ? true : 'pending';
                            x.earned = completionDetails.xp || 0;
                        }
                        return x;
                    });

                    renderAnnotationItems(annotations, self.start, self.end - self.start);
                    thisItem.earned = completionDetails.xp || 0;
                    // Play a popup sound.
                    let audio = new Audio(M.cfg.wwwroot + '/mod/interactivevideo/sounds/point-awarded.mp3');
                    audio.play();
                    self.dispatchEvent('completionupdated', {
                        annotations,
                        completionpercentage: (completedItems.length / gradableitems.length) * 100,
                        grade: parseFloat((earnedXp / totalXp) * self.grademax).toFixed(2),
                        completed,
                        xp: earnedXp,
                        completeditems: completedItems,
                        target: thisItem,
                        action: 'mark-done',
                        type,
                        response: res,
                    });
                    resolve();
                }
            });
        });
    }

    /**
     * Add an annotation
     * @param {Array} annotations The annotations array
     * @param {number} timestamp The timestamp
     * @param {number} coursemodule The course module id
     * @returns {void}
     */
    addAnnotation(annotations, timestamp, coursemodule) {
        $('#addcontent, #importcontent').addClass('no-pointer-events');
        let self = this;
        this.annotations = annotations;

        const startHMS = self.convertSecondsToHMS(self.start);
        const endHMS = self.convertSecondsToHMS(self.end);
        const timestampHMS = timestamp > 0 ? self.convertSecondsToHMS(timestamp) : startHMS;

        const data = {
            id: 0,
            timestamp: -1,
            timestampassist: timestampHMS,
            title: self.prop.title,
            start: startHMS,
            end: endHMS,
            contextid: M.cfg.contextid,
            type: self.prop.name,
            courseid: self.course,
            cmid: coursemodule,
            annotationid: self.interaction,
            hascompletion: self.prop.hascompletion ? 1 : 0,
        };

        $('#annotationwrapper table').hide();
        $('#annotationwrapper').append('<div id="form" class="w-100 p-3"></div>');
        $("#contentmodal").modal('hide');
        $('#addcontentdropdown a').removeClass('active');

        const selector = document.querySelector(`#annotationwrapper #form`);
        const decisionform = new DynamicForm(selector, self.prop.form);
        decisionform.load(data);

        self.onEditFormLoaded(decisionform);
        self.validateTimestampFieldValue('timestampassist', 'timestamp');

        $(document).off('click', '#cancel-submit').on('click', '#cancel-submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            $('#annotationwrapper #form').remove();
            $('#annotationwrapper table').show();
            $('#addcontent, #importcontent').removeClass('no-pointer-events');
            // Remove preview.
            $('#annotation-canvas #badge-preview').remove();
        });

        $(document).off('click', '#submitform-submit').on('click', '#submitform-submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const event = decisionform.trigger(decisionform.events.SUBMIT_BUTTON_PRESSED);
            if (!event.defaultPrevented) {
                decisionform.submitFormAjax();
            }
            // Remove preview.
            $('#annotation-canvas #badge-preview').remove();
        });

        decisionform.addEventListener(decisionform.events.SERVER_VALIDATION_ERROR, (e) => {
            e.stopImmediatePropagation();
            self.onEditFormLoaded(decisionform);
            self.validateTimestampFieldValue('timestampassist', 'timestamp');
        });

        decisionform.addEventListener(decisionform.events.FORM_SUBMITTED, (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();
            $.ajax({
                url: M.cfg.wwwroot + '/mod/interactivevideo/ajax.php',
                method: "POST",
                dataType: "text",
                data: {
                    action: 'get_item',
                    id: e.detail.id,
                    sesskey: M.cfg.sesskey,
                    contextid: M.cfg.courseContextId,
                    token: self.token,
                    cmid: self.cm,
                },
                success: function(data) {
                    const newAnnotation = JSON.parse(data);
                    self.dispatchEvent('annotationupdated', {
                        annotation: newAnnotation,
                        action: 'add'
                    });
                }
            });
            $('#annotationwrapper #form').remove();
            $('#annotationwrapper table').show();
            $('#addcontent, #importcontent').removeClass('no-pointer-events');
        });
    }

    /**
     * Edit an annotation
     * @param {Array} annotations The annotations array
     * @param {number} id The annotation id
     * @returns {void}
     */
    editAnnotation(annotations, id) {
        // Disable pointer events on some DOMs.
        $('#addcontent, #importcontent').addClass('no-pointer-events');
        this.annotations = annotations;
        let self = this;
        const annotation = annotations.find(x => x.id == id);
        const timestamp = annotation.timestamp;
        const timestampassist = this.convertSecondsToHMS(timestamp);

        annotation.timestampassist = timestampassist;
        annotation.start = this.convertSecondsToHMS(this.start);
        annotation.end = this.convertSecondsToHMS(this.end);
        annotation.contextid = M.cfg.contextid;

        $('#annotationwrapper table').hide();
        $('#annotationwrapper').append('<div id="form" class="w-100 p-3"></div>');
        const selector = document.querySelector(`#annotationwrapper #form`);
        const decisionform = new DynamicForm(selector, self.prop.form);
        decisionform.load(annotation);

        self.onEditFormLoaded(decisionform);
        self.validateTimestampFieldValue('timestampassist', 'timestamp');

        $(document).off('click', '#cancel-submit').on('click', '#cancel-submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            $('#annotationwrapper #form').remove();
            $('#annotationwrapper table').show();
            $('#addcontent, #importcontent').removeClass('no-pointer-events');
            // Remove preview.
            $('#annotation-canvas #badge-preview').remove();
        });

        $(document).off('click', '#submitform-submit').on('click', '#submitform-submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const event = decisionform.trigger(decisionform.events.SUBMIT_BUTTON_PRESSED);
            if (!event.defaultPrevented) {
                decisionform.submitFormAjax();
            }
            // Remove preview.
            $('#annotation-canvas #badge-preview').remove();
        });

        decisionform.addEventListener(decisionform.events.SERVER_VALIDATION_ERROR, (e) => {
            e.stopImmediatePropagation();
            self.onEditFormLoaded(decisionform);
            self.validateTimestampFieldValue('timestampassist', 'timestamp');
        });

        decisionform.addEventListener(decisionform.events.FORM_SUBMITTED, (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.annotations = this.annotations.filter(x => x.id != id);
            $.ajax({
                url: M.cfg.wwwroot + '/mod/interactivevideo/ajax.php',
                method: "POST",
                dataType: "text",
                data: {
                    action: 'get_item',
                    id: e.detail.id,
                    sesskey: M.cfg.sesskey,
                    contextid: M.cfg.courseContextId,
                    token: self.token,
                    cmid: self.cm,
                },
            }).done(function(data) {
                const updated = JSON.parse(data);
                self.dispatchEvent('annotationupdated', {
                    annotation: updated,
                    action: 'edit'
                });
            });
            $('#annotationwrapper #form').remove();
            $('#annotationwrapper table').show();
            $('#addcontent, #importcontent').removeClass('no-pointer-events');
        });
    }

    /**
     * Get the position of the element.
     * @param {String} position The position.
     * @returns {String} The position.
     */
    getPosition(position = 'centercenter') {
        let classes = '';
        switch (position) {
            case 'top-left':
                classes = 'align-items-start justify-content-start';
                break;
            case 'top-center':
                classes = 'align-items-start justify-content-center';
                break;
            case 'top-right':
                classes = 'align-items-start justify-content-end';
                break;
            case 'center-left':
                classes = 'align-items-center justify-content-start';
                break;
            case 'center-center':
                classes = 'align-items-center justify-content-center';
                break;
            case 'center-right':
                classes = 'align-items-center justify-content-end';
                break;
            case 'bottom-left':
                classes = 'align-items-end justify-content-start';
                break;
            case 'bottom-center':
                classes = 'align-items-end justify-content-center';
                break;
            case 'bottom-right':
                classes = 'align-items-end justify-content-end';
                break;
            default:
                break;
        }
        return classes;
    }

    /**
     * Handles the loading of the edit form and initializes the destination list.
     *
     * @param {HTMLElement} form - The form element that is being edited.
     * @param {Event} event - The event that triggered the form load.
     * @returns {Object} An object containing the form and event.
     */
    onEditFormLoaded(form, event) {
        let self = this;
        let body = $('#annotationwrapper #form');
        let positions = [
            {
                'name': 'top-left',
                'label': M.util.get_string('topleft', 'block_rajabsgames'),
            },
            {
                'name': 'top-center',
                'label': M.util.get_string('topcenter', 'block_rajabsgames'),
            },
            {
                'name': 'top-right',
                'label': M.util.get_string('topright', 'block_rajabsgames'),
            },
            {
                'name': 'center-left',
                'label': M.util.get_string('centerleft', 'block_rajabsgames'),
            },
            {
                'name': 'center-center',
                'label': M.util.get_string('centercenter', 'block_rajabsgames'),
                'selected': 'selected'
            },
            {
                'name': 'center-right',
                'label': M.util.get_string('centerright', 'block_rajabsgames'),
            },
            {
                'name': 'bottom-left',
                'label': M.util.get_string('bottomleft', 'block_rajabsgames'),
            },
            {
                'name': 'bottom-center',
                'label': M.util.get_string('bottomcenter', 'block_rajabsgames'),
            },
            {
                'name': 'bottom-right',
                'label': M.util.get_string('bottomright', 'block_rajabsgames'),
            }
        ];
        const checkContentField = () => {
            if ($('[name=content]').length > 0) {
                let dest = $('[name=content]').val();
                let badgeoptions = $('[name=badges]').val();
                if (badgeoptions) {
                    badgeoptions = JSON.parse(badgeoptions);
                } else {
                    badgeoptions = [];
                }
                if (dest == '' || JSON.parse(dest).length == 0) {
                    $('#destination-list').append(`<div class="input-group mb-1 d-none">
                <input type="text" class="uniqueid form-control" value=""
                     placeholder="${M.util.get_string('uniquename', 'block_rajabsgames')}">
                <select class="custom-select form-select badgeoption">
                <option value="">${M.util.get_string('selectabadge', 'block_rajabsgames')}</option>
                        ${badgeoptions.map((badge) => {
                        return `<option value="${badge.id}">${badge.name}</option>`;
                    }).join('')}
                </select>
                <select class="custom-select form-select positionoption">
                        ${positions.map((position) => {
                        return `<option value="${position.name}" ${position.selected}>${position.label}</option>`;
                    }).join('')}
                </select>
                <input type="text" class="form-control xp" value="0" placeholder="${M.util.get_string('xp', 'block_rajabsgames')}">
                <input type="text" value="${this.convertSecondsToHMS(this.start)}"
                placeholder="00:00:00" style="max-width: 120px;" class="form-control timestamp-input"
                 title="${M.util.get_string('rightclicktousecurrenttime', 'block_rajabsgames')}">
                <div class="input-group-append">
                <button class="btn goto-dest btn-secondary px-2" type="button"><i class="bi bi-play-fill fs-25px"></i></button>
                <button class="btn add-dest btn-secondary" type="button"><i class="bi bi-plus-lg fs-unset"></i></button>
                <button class="btn btn-danger delete-dest disabled" disabled type="button">
                    <i class="bi bi-trash3-fill fs-unset"></i></button></div></div>`);
                } else {
                    dest = JSON.parse(dest);
                    dest.forEach((d, i) => {
                        $('#destination-list').append(`<div class="input-group mb-1">
                    <input type="text" class="uniqueid form-control" value="${d.uniqueid}"
                     placeholder="${M.util.get_string('uniquename', 'block_rajabsgames')}">
                    <select class="custom-select form-select badgeoption">
                        <option value="">${M.util.get_string('selectabadge', 'block_rajabsgames')}</option>
                                ${badgeoptions.map((badge) => {
                            return `<option value="${badge.id}" ${badge.id == d.badgeid ? 'selected' : ''}>${badge.name}</option>`;
                        }).join('')}
                        </select>
                    <select class="custom-select form-select positionoption">
                            ${positions.map((position) => {
                            return `<option value="${position.name}"
                             ${position.name == d.position ? 'selected' : ''}>${position.label}</option>`;
                        }).join('')}
                    </select>
                    <input type="text" class="form-control xp" value="${d.xp || 0}"
                     placeholder="${M.util.get_string('xp', 'block_rajabsgames')}">
                    <input type="text" value="${this.convertSecondsToHMS(d.timestamp)}"
                    placeholder="00:00:00" style="max-width: 120px;" class="form-control timestamp-input"
                     title="${M.util.get_string('rightclicktousecurrenttime', 'block_rajabsgames')}">
                    <div class="input-group-append">
                    <button class="btn goto-dest btn-secondary px-2" type="button"><i class="bi bi-play-fill fs-25px"></i></button>
                    <button class="btn add-dest btn-secondary" type="button"><i class="bi bi-plus-lg fs-unset"></i></button>
                    <button class="btn btn-danger delete-dest ${i == 0 ? 'disabled' : ''}" ${i == 0 ? 'disabled' : ''}
                    type="button"><i class="bi bi-trash3-fill fs-unset"></i></button></div></div>`);
                    });
                    $('.input-group [type="text"]').trigger('input');
                }
            } else {
                requestAnimationFrame(checkContentField);
            }
        };

        requestAnimationFrame(checkContentField);

        body.off('click', '#add-destination').on('click', '#add-destination', function() {
            const $last = $('#destination-list .input-group').last();
            $last.find('.add-dest').trigger('click');
        });

        body.off('click', '.input-group .add-dest').on('click', '.input-group .add-dest', async function(e) {
            e.stopImmediatePropagation();
            let $thisrow = $(this);
            let $parent = $thisrow.closest('.input-group');
            let $row = $parent.clone();
            $row.removeClass('d-none');
            let currentTime = await self.player.getCurrentTime();
            $row.find('input.timestamp-input').val(self.convertSecondsToHMS(currentTime));
            $row.find('.uniqueid').val('');
            $row.find('.xp').val(0);
            $row.find('.delete-dest').removeClass('disabled').removeAttr('disabled');
            $parent.after($row);
            $parent.find('[type="text"]').trigger('input');
        });

        body.off('click', '.input-group .delete-dest').on('click', '.input-group .delete-dest', function(e) {
            e.stopImmediatePropagation();
            $(this).closest('.input-group').remove();
            $('.input-group [type="text"]').trigger('input');
        });

        body.off('click', '.input-group .goto-dest').on('click', '.input-group .goto-dest', async function(e) {
            e.stopImmediatePropagation();
            $('#annotation-canvas #badge-preview').remove();
            const timestamp = $(this).closest('.input-group').find('input.timestamp-input').val();
            const seconds = self.convertHMSToSeconds(timestamp);
            const position = $(this).closest('.input-group').find('.positionoption').val();
            const badgeid = $(this).closest('.input-group').find('.badgeoption').val();
            const $badge = $(`.rg_badge_item[data-id="${badgeid}"]`).clone();
            const xp = $(this).closest('.input-group').find('.xp').val();
            if (xp > 0) {
                $badge.append(`<small class="text-muted">
                    ${xp} <sup>${M.util.get_string('xp', 'mod_interactivevideo')}</sup></span>`);
            }
            let classes = self.getPosition(position);
            const currentTime = await self.player.getCurrentTime();
            if (currentTime != seconds) {
                await self.player.seek(seconds);
            }
            $('#annotation-canvas')
                .append($('<div id="badge-preview" class="d-flex h-100 w-100 ' + classes + ' p-2 z-index-1"></div>')
                    .append($badge));
        });

        body.off('contextmenu', '.input-group .timestamp-input')
            .on('contextmenu', '.input-group .timestamp-input', async function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                // Get the current time of the video.
                let currentTime = await self.player.getCurrentTime();
                $(this).val(self.convertSecondsToHMS(currentTime));
                $(this).trigger('input');
            });

        body.on('input', '.input-group :input', function() {
            let dest = [];
            $('#destination-list .input-group').each(function() {
                const badgeid = $(this).find('.badgeoption').val();
                const xp = $(this).find('.xp').val() || 0;
                const position = $(this).find('.positionoption').val();
                const $timestamp = $(this).find('.timestamp-input');
                const uniqueid = $(this).find('.uniqueid').val();
                if (uniqueid != "" && badgeid != '' && $timestamp.val() && !$timestamp.hasClass('is-invalid')) {
                    const seconds = self.convertHMSToSeconds($timestamp.val());
                    dest.push({
                        badgeid,
                        xp,
                        position,
                        timestamp: seconds,
                        uniqueid
                    });
                }
            });
            dest.sort((a, b) => a.timestamp - b.timestamp);
            // Make sure dest are unique based on timestamps and uniqueid
            dest = dest.filter((d, i) => {
                if (i == 0) {
                    return true;
                }
                if (d.timestamp == dest[i - 1].timestamp) {
                    return false;
                }
                if (d.uniqueid == dest[i - 1].uniqueid) {
                    return false;
                }
                return true;
            });
            // Total XP.
            let totalxp = 0;
            dest.forEach((d) => {
                totalxp += Number(d.xp);
            });
            if (dest.length == 0) {
                $('form [name=content]').val('');
                $('form [name=xp]').val(0);
            } else {
                $('form [name=content]').val(JSON.stringify(dest));
                $('form [name=xp]').val(totalxp);
            }
        });

        $(document).off('timeupdate.rajabsgames').on('timeupdate.rajabsgames', async function() {
            // Remove the preview.
            $('#annotation-canvas #badge-preview').remove();

        });

        return {form, event};
    }

    /**
     * Run the interaction
     * @param {object} annotation The annotation object
     * @returns {void}
     */
    async runInteraction(annotation) {
        // Dismiss all tooltips.
        $('.tooltip').remove();
        await this.player.pause();
        let self = this;
        let dest = JSON.parse(annotation.content);
        dest = dest.filter((d) => {
            return !self.isSkipped(d.timestamp);
        });

        if (!self.isEditMode()) {
            annotation.viewed = true;
            this.annotations = this.annotations.filter((d) => d.id != annotation.id);
            this.annotations.push(annotation);
        }

        if (dest.length == 0) {
            this.player.play();
            return;
        }

        let newannotation = JSON.parse(JSON.stringify(annotation));
        newannotation.content = JSON.stringify(dest);
        // We don't need to run the render method every time the content is applied. We can cache the content.
        if (!self.cache[annotation.id] || self.isEditMode()) {
            self.cache[annotation.id] = await this.render(newannotation, 'json');
        }
        const data = self.cache[annotation.id];
        let $html = `<div class="position-absolute decision text-center mx-auto w-100">
            <h5 class="pt-5 pb-3 bg-white" id="decision-q">
            <i class="mb-2 bi bi-signpost-split-fill" style="font-size: 2em"></i><br>${newannotation.formattedtitle}</h5>`;

        data.forEach((dest, order) => {
            $html += `<a href="javascript:void(0)" data-timestamp="${dest.timestamp}"
                 data-order="${order}" class="decision-option btn btn-outline-secondary btn-rounded mb-2 d-flex
                  justify-content-between align-items-center mx-auto"><span class="text-truncate">${dest.title}</span>
                  <i class="bi bi-chevron-right"></i></a>`;
        });
        $html += '</div>';
        let $message = $(`<div id="message" style="z-index:1005;display:none;" data-id="${annotation.id}" tabindex="0">
            <div class="modal-body p-0 border" id="content">${$html}</div></div>`);
        $('#video-wrapper').find("#message").remove();
        $('#video-wrapper').append($message);

        $message.fadeIn(300, 'swing', async function() {
            // Set focus on the #message element
            $('body').addClass('disablekb');
            document.querySelector(`#message[data-id='${annotation.id}']`).focus();
            if (annotation.char1 == 1) {
                $message.append(`<button class="btn btn-secondary btn-rounded position-absolute"
                     id="close-decision" style="right: 1rem; top: 1rem;">
                     ${M.util.get_string('skip', 'local_ivdecision')}
                     <i class="iv-ml-2 bi bi-chevron-right"></i></button>`);
            }

            $(document).off('click', '#close-decision').on('click', '#close-decision', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                self.player.play();
                $('#taskinfo').fadeIn(300);
                $('[data-region="chapterlists"], #controller').removeClass('no-pointer-events');
            });

            if (!self.isEditMode()) {
                $('#taskinfo').fadeOut(300);
                $('[data-region="chapterlists"], #controller').addClass('no-pointer-events');
            }
            await self.player.pause();
        });

        $(document).off('click', `#message[data-id='${annotation.id}'] .decision-option`)
            .on('click', `#message[data-id='${annotation.id}'] .decision-option`, function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                let time = Number($(this).data('timestamp'));
                if (time < this.start) {
                    time = this.start;
                } else if (time > this.end) {
                    time = this.end;
                }
                self.player.seek(time);
                $(`#message[data-id='${annotation.id}']`).fadeOut(300);
                self.player.play();
                $('#taskinfo').fadeIn(300);
                $('[data-region="chapterlists"], #controller').removeClass('no-pointer-events');
            });
    }

    postEditCallback() {
        // Do not do the default post edit callback.
    }

    renderEditItem(annotations, listItem, item) {
        listItem = super.renderEditItem(annotations, listItem, item);
        listItem.find('[data-editable="xp"]').removeAttr('data-editable');
        return listItem;
    }
}