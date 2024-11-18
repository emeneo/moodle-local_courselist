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

/**
 * Capabilities
 *
 * Defines capablities related to courselist
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_courselist_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager(); // Returns database manager instance.

    // Check if upgrading to a version that includes new capabilities.
    if ($oldversion < 2024101109) { // Replace with your new version number.
        // Define the capability.
        $capability = 'local/courselist:view';
        
        // Ensure that the capability is defined.
        if (!$DB->record_exists('capabilities', ['name' => $capability])) {
            // Add the capability if it does not exist.
            $DB->insert_record('capabilities', [
                'name' => $capability,
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => json_encode([]), // Optional: leave empty or configure as needed.
                'description' => 'Allows users to view the page.',
            ]);
        }

        // Assign the capability to authenticated users.
        $authenticateduser_role = $DB->get_record('role', ['shortname' => 'frontpage']);
        if ($authenticateduser_role) {
            $context = context_system::instance();
            assign_capability($capability, CAP_ALLOW, $authenticateduser_role->id, $context->id, true);
        }

        $role = $DB->get_record('role', ['shortname' => 'user']); // You can specify the role you want.
        if ($role) {
            $context = context_system::instance();
            assign_capability($capability, CAP_ALLOW, $role->id, $context->id, true);
        }

        // Assign the capability to all users in the site context.
        /*
        $role = $DB->get_record('role', ['shortname' => 'student']); // You can specify the role you want.
        if ($role) {
            $context = context_system::instance();
            assign_capability($capability, CAP_ALLOW, $role->id, $context->id, true);
        }
        */
        // Mark the plugin as upgraded to the new version.
        upgrade_plugin_savepoint(true, 2024101109, 'local', 'courselist');
    }
    return true;
}