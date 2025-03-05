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

$capabilities = [
    'local/courselist:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'authenticateduser' => CAP_ALLOW, // Allow all logged-in users.
            'manager' => CAP_ALLOW, // Allow managers by default.
            'coursecreator' => CAP_ALLOW, // Allow course creators by default.
            'editingteacher' => CAP_ALLOW, // Allow editing teachers by default.
            'teacher' => CAP_ALLOW, // Allow non-editing teachers by default.
            'student' => CAP_ALLOW, // Allow students by default.
            'guest' => CAP_PREVENT, // Prevent guests by default.
            'frontpage' => CAP_ALLOW, // Allow frontpage users by default. 
        ],
    ],
    'local/courselist:manage' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'authenticateduser' => CAP_PREVENT, // Allow all logged-in users.
            'manager' => CAP_ALLOW, // Allow managers by default.
            'editingteacher' => CAP_PREVENT, // Allow editing teachers by default.
            'teacher' => CAP_PREVENT, // Allow non-editing teachers by default.
            'student' => CAP_PREVENT, // Allow students by default.
            'guest' => CAP_PREVENT, // Prevent guests by default.
            'frontpage' => CAP_PREVENT, // Allow frontpage users by default.
        ],
    ],
];


