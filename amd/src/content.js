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
 * @package   block_assessments
 * @copyright 2022 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'core/log', 'core/ajax'], function($, Log, Ajax) {
  "use strict";

  return {
      init: function(userid) {
          Log.debug('block_assessments: initializing');

          if (!userid) {
              Log.error('block_assessments: userid not provided!');
              return;
          }

          var rootel = $('.block_assessments .root').first();
          if (!rootel.length) {
              Log.error('block_assessments: root element not found!');
              return;
          }

          // Handle turn off audit mode button.
          rootel.on('click', '.block_assessments_toggle', function(e) {
              e.preventDefault();
              var open = rootel.attr('data-toggle');
              Log.debug('block_assessments: Toggle currently ' + open);
              var toggle = (open == '1') ? 0 : 1;
              toggleBlock(toggle, userid, rootel);
          });

          // Show past courses.
          rootel.on('click', '.btn-showpast', function(e) {
            e.preventDefault();
            if (rootel.data('showingpast') == true) {
              rootel.find('tr.assessment[data-eventorder="Past"]').hide();
              rootel.data('showingpast', false);
              $(this).html("Show past assessments");
            } else {
              rootel.find('tr.assessment[data-eventorder="Past"]').show();
              rootel.data('showingpast', true);
              $(this).html("Hide past assessments");
            }
          });
          
      }
  };


  /**
   * Click handler to toggle block.
   * @param int toggle. Open or closed.
   * @param int the user id.
   * @param {jQuery} rootel.
   */
    function toggleBlock(toggle, userid, rootel) {
      // Save toggle state as a preference.
      Log.debug('block_assessments: Setting toggle to ' + toggle);
      rootel.attr('data-toggle', toggle+"");
      var preferences = [{
          'name': 'block_assessments_toggle',
          'value': toggle,
          'userid': userid
      }];
      Ajax.call([{
          methodname: 'core_user_set_user_preferences',
          args: { preferences: preferences },
          done: function(response) {
            console.log(response)
          }
      }]);
    }
});