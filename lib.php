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
 * local_cousrse lib.
 *
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

/**
 * get custom field categories
 */
function local_courselist_getcustomfieldcategories()
{
    global $DB;
    $raws = $DB->get_records('customfield_category', [], '', 'id,name');
    return $raws;
}

/**
 * get custom field
 */
function local_courselist_getcustomfield($cateid)
{
    global $DB;
    $raws = $DB->get_records('customfield_field', ['categoryid' => $cateid], 'sortorder ASC', 'id,name,shortname,description,type,configdata');
    return $raws;
}

/**
 * get course by custom field
 */
function local_courselist_getcoursebycustomfield($fieldid)
{
    $cache = cache::make('local_courselist', 'somedata');
    global $DB;
    $rows = @unserialize($cache->get('coursebyfield:' . $fieldid));
    if (!$rows) {
        $sql = "SELECT instanceid 
            FROM {customfield_data} 
            WHERE intvalue = 1 
                AND fieldid = :fieldid";
        $subquery = $DB->get_records_sql($sql, ['fieldid' => $fieldid]);
        $instanceids = array_keys($subquery);
        $rows = $DB->get_records_list('course', 'id', $instanceids, 'fullname ASC');
        foreach ($rows as $k => $row) {
            if ($row->visible <= 0) {
                unset($rows[$k]);
            }
        }
        $cache->set('coursebyfield:' . $fieldid, serialize($rows), 600);
    }
    return $rows;
}

/**
 * get free seats
 */
function local_courselist_getfreeseats($courseid)
{
    global $DB;
    $seatssummary = '';
    $sql = "SELECT id, enrol, customint3 
            FROM {enrol} 
            WHERE courseid = :courseid 
                AND status = 0 
                ORDER BY sortorder ASC LIMIT 1";
    $enrol = $DB->get_record_sql($sql, ["courseid" => $courseid]);
    if ($enrol) {
        $enrolment = $DB->count_records('user_enrolments', ['enrolid' => $enrol->id]);
        if ($enrol->customint3 > 0) {
            $seatssummary = ($enrol->customint3 - $enrolment) . " " . get_string('out_of', 'local_courselist') . " " . $enrol->customint3;
            if ($enrolment == $enrol->customint3 && $enrol->enrol == "waitlist") {
                $seatssummary .= ", " . get_string('waitlist_possible', 'local_courselist');
            }
        } else {
            $seatssummary = get_string('unlimited', 'local_courselist');
        }
    }
    return $seatssummary;
}

/**
 * get course by key
 */
function local_courselist_getcoursebykey($key, $categoryid)
{
    global $DB;
    [$insql, $inparams] = $DB->get_in_or_equal($categoryid);
    /*
    $sql = "SELECT * FROM {customfield_field} 
            WHERE categoryid $insql";
    $fieldIds = $DB->get_records_sql($sql, $inparams);
    */
    $sql = "SELECT * FROM {customfield_field} WHERE categoryid IN (" . $categoryid . ")";
    $fieldIds = $DB->get_records_sql($sql);
    $fieldIdArray = array_keys($fieldIds);
    [$insql, $inparams] = $DB->get_in_or_equal($fieldIdArray);
    $sql = "SELECT instanceid 
            FROM {customfield_data} 
            WHERE intvalue=1 AND fieldid $insql 
            GROUP BY instanceid";
    $raws = $DB->get_records_sql($sql, $inparams);
    $courseids = [];
    foreach ($raws as $raw) {
        $courseids[] = $raw->instanceid;
    }
    /*
    $likeKey = $DB->sql_like('fullname', ':key');
    $raws = $DB->get_records_sql(
        "SELECT * FROM {course} WHERE {$likeKey}",
        [
            'key' => '%' . $key . '%',
        ]
    );
    */
    $raws = $DB->get_records_sql("SELECT * FROM {course} WHERE visible=1 AND fullname like '%" . $key . "%'");
    foreach ($raws as $k => $raw) {
        if (!in_array($raw->id, $courseids)) {
            unset($raws[$k]);
            continue;
        }

        $sql = "SELECT fieldid 
                FROM {customfield_data} 
                WHERE intvalue = 1 
                    AND instanceid = :instanceid";
        $field = $DB->get_records_sql($sql, ['instanceid' => $raw->id]);
        $fieldids = [];
        foreach ($field as $f) {
            $fieldids[] = $f->fieldid;
        }
        $raws[$k]->fieldid = $fieldids;
    }
    return $raws;
}

/**
 * get course image by itemid
 */
function local_courselist_getcourseimagebykey($itemid)
{
    global $DB;
    $fileUrl = '';
    $sql = "SELECT * FROM {files} WHERE itemid = :itemid and filename<>'.'";
    $rows = $DB->get_records_sql($sql, ['itemid' => $itemid]);
    foreach ($rows as $row) {
        if ($row->filesize > 0) {
            $output = [
                'hash' => $row->contenthash,
                'mime' => $row->mimetype,
                'filename' => $row->filename
            ];
            $fileUrl = new moodle_url("/local/courselist/show.php?d=" . base64_encode(json_encode($output)));
        }
    }
    return $fileUrl;
}

/**
 * get course cover by random
 */
function local_courselist_getrandcover()
{
    $images = [
        'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIj8+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMzYiIGhlaWdodD0iMjM1Ij48cmVjdCB4PSIwIiB5PSIwIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJyZ2IoMjIzLCAyMzAsIDIzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wODA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0yMi42NjY2NjY2NjY2NjcsIDApIHJvdGF0ZSgxODAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA4MDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTEzLjMzMzMzMzMzMzMzLCAwKSByb3RhdGUoMTgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wODA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsIDApIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjIuNjY2NjY2NjY2NjY3LCAwKSByb3RhdGUoMTgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDUuMzMzMzMzMzMzMzMzLCAwKSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDcyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg2OCwgMCkgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDg5MzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5MC42NjY2NjY2NjY2NjcsIDApIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTIyLjY2NjY2NjY2NjY2NywgMzkuMjU5ODE4MzA0ODk1KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTMyNjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDExMy4zMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsIDM5LjI1OTgxODMwNDg5NSkgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTQxMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIyLjY2NjY2NjY2NjY2NywgMzkuMjU5ODE4MzA0ODk1KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTI0IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSkgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTMyNjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDY4LCAzOS4yNTk4MTgzMDQ4OTUpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wODkzMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkwLjY2NjY2NjY2NjY2NywgMzkuMjU5ODE4MzA0ODk1KSByb3RhdGUoMTgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0yMi42NjY2NjY2NjY2NjcsIDc4LjUxOTYzNjYwOTc4OSkgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDM3MzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMTMuMzMzMzMzMzMzMzMsIDc4LjUxOTYzNjYwOTc4OSkgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDQ2IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCA3OC41MTk2MzY2MDk3ODkpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMTUzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjIuNjY2NjY2NjY2NjY3LCA3OC41MTk2MzY2MDk3ODkpIHJvdGF0ZSgxODAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0NS4zMzMzMzMzMzMzMzMsIDc4LjUxOTYzNjYwOTc4OSkgcm90YXRlKDAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjExNTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg2OCwgNzguNTE5NjM2NjA5Nzg5KSByb3RhdGUoMTgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMTUzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoOTAuNjY2NjY2NjY2NjY3LCA3OC41MTk2MzY2MDk3ODkpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMTUzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTIyLjY2NjY2NjY2NjY2NywgMTE3Ljc3OTQ1NDkxNDY4KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTE1MzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDExMy4zMzMzMzMzMzMzMywgMTE3Ljc3OTQ1NDkxNDY4KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDYzMzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAxMTcuNzc5NDU0OTE0NjgpIHJvdGF0ZSgxODAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA4OTMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjIuNjY2NjY2NjY2NjY3LCAxMTcuNzc5NDU0OTE0NjgpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDUuMzMzMzMzMzMzMzMzLCAxMTcuNzc5NDU0OTE0NjgpIHJvdGF0ZSgxODAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjEzMjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg2OCwgMTE3Ljc3OTQ1NDkxNDY4KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTE1MzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkwLjY2NjY2NjY2NjY2NywgMTE3Ljc3OTQ1NDkxNDY4KSByb3RhdGUoMTgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wMjg2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0yMi42NjY2NjY2NjY2NjcsIDE1Ny4wMzkyNzMyMTk1OCkgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDI4NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMTMuMzMzMzMzMzMzMzMsIDE1Ny4wMzkyNzMyMTk1OCkgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTI0IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAxNTcuMDM5MjczMjE5NTgpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wODkzMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIyLjY2NjY2NjY2NjY2NywgMTU3LjAzOTI3MzIxOTU4KSByb3RhdGUoMTgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDUuMzMzMzMzMzMzMzMzLCAxNTcuMDM5MjczMjE5NTgpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wMjg2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDY4LCAxNTcuMDM5MjczMjE5NTgpIHJvdGF0ZSgxODAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoOTAuNjY2NjY2NjY2NjY3LCAxNTcuMDM5MjczMjE5NTgpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTIyLjY2NjY2NjY2NjY2NywgMTk2LjI5OTA5MTUyNDQ3KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTMyNjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDExMy4zMzMzMzMzMzMzMywgMTk2LjI5OTA5MTUyNDQ3KSByb3RhdGUoMCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjIyLjY2NjY2NjY2NjY2NywgMCwgNDUuMzMzMzMzMzMzMzMzLCAzOS4yNTk4MTgzMDQ4OTUsIDAsIDM5LjI1OTgxODMwNDg5NSwgMjIuNjY2NjY2NjY2NjY3LCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAxOTYuMjk5MDkxNTI0NDcpIHJvdGF0ZSgxODAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjAyODY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjIuNjY2NjY2NjY2NjY3LCAxOTYuMjk5MDkxNTI0NDcpIHJvdGF0ZSgwLCAyMi42NjY2NjY2NjY2NjcsIDE5LjYyOTkwOTE1MjQ0NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMjIuNjY2NjY2NjY2NjY3LCAwLCA0NS4zMzMzMzMzMzMzMzMsIDM5LjI1OTgxODMwNDg5NSwgMCwgMzkuMjU5ODE4MzA0ODk1LCAyMi42NjY2NjY2NjY2NjcsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDUuMzMzMzMzMzMzMzMzLCAxOTYuMjk5MDkxNTI0NDcpIHJvdGF0ZSgxODAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA3MiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNjgsIDE5Ni4yOTkwOTE1MjQ0Nykgcm90YXRlKDAsIDIyLjY2NjY2NjY2NjY2NywgMTkuNjI5OTA5MTUyNDQ3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIyMi42NjY2NjY2NjY2NjcsIDAsIDQ1LjMzMzMzMzMzMzMzMywgMzkuMjU5ODE4MzA0ODk1LCAwLCAzOS4yNTk4MTgzMDQ4OTUsIDIyLjY2NjY2NjY2NjY2NywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjE0MTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5MC42NjY2NjY2NjY2NjcsIDE5Ni4yOTkwOTE1MjQ0Nykgcm90YXRlKDE4MCwgMjIuNjY2NjY2NjY2NjY3LCAxOS42Mjk5MDkxNTI0NDcpIiAvPjwvc3ZnPg==',
        'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIj8+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MTMiIGhlaWdodD0iNDEzIj48cmVjdCB4PSIwIiB5PSIwIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJyZ2IoMCwgMTg0LCAxNDgpIiAvPjxyZWN0IHg9IjAiIHk9IjYiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjE2IiBvcGFjaXR5PSIwLjExNTMzMzMzMzMzMzMzIiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjAiIHk9IjMzIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSI5IiBvcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSI1MiIgd2lkdGg9IjEwMCUiIGhlaWdodD0iOCIgb3BhY2l0eT0iMC4wNDYiIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMCIgeT0iNzMiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjE0IiBvcGFjaXR5PSIwLjA5OCIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIwIiB5PSI5NCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iOSIgb3BhY2l0eT0iMC4wNTQ2NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMCIgeT0iMTE1IiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSI4IiBvcGFjaXR5PSIwLjA0NiIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIwIiB5PSIxMzgiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjkiIG9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjAiIHk9IjE1OCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTIiIG9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjAiIHk9IjE4OCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iNSIgb3BhY2l0eT0iMC4wMiIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSIyMDUiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjgiIG9wYWNpdHk9IjAuMDQ2IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjAiIHk9IjIyNSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iNyIgb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMCIgeT0iMjUwIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSI5IiBvcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSIyNjkiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjE5IiBvcGFjaXR5PSIwLjE0MTMzMzMzMzMzMzMzIiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjAiIHk9IjMwNCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iNSIgb3BhY2l0eT0iMC4wMiIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSIzMTkiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjE1IiBvcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjAiIHk9IjM1MCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTciIG9wYWNpdHk9IjAuMTI0IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjAiIHk9IjM3NCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iNSIgb3BhY2l0eT0iMC4wMiIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSIzODciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjYiIG9wYWNpdHk9IjAuMDI4NjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjAiIHk9IjQwNCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iOSIgb3BhY2l0eT0iMC4wNTQ2NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iNiIgeT0iMCIgd2lkdGg9IjE2IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMTE1MzMzMzMzMzMzMzMiIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMzMiIHk9IjAiIHdpZHRoPSI5IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjUyIiB5PSIwIiB3aWR0aD0iOCIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjA0NiIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSI3MyIgeT0iMCIgd2lkdGg9IjE0IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMDk4IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9Ijk0IiB5PSIwIiB3aWR0aD0iOSIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIxMTUiIHk9IjAiIHdpZHRoPSI4IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMDQ2IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjEzOCIgeT0iMCIgd2lkdGg9IjkiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wNTQ2NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMTU4IiB5PSIwIiB3aWR0aD0iMTIiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wODA2NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMTg4IiB5PSIwIiB3aWR0aD0iNSIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjAyIiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjIwNSIgeT0iMCIgd2lkdGg9IjgiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wNDYiIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMjI1IiB5PSIwIiB3aWR0aD0iNyIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjAzNzMzMzMzMzMzMzMzMyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIyNTAiIHk9IjAiIHdpZHRoPSI5IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjI2OSIgeT0iMCIgd2lkdGg9IjE5IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMTQxMzMzMzMzMzMzMzMiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMzA0IiB5PSIwIiB3aWR0aD0iNSIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjAyIiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjMxOSIgeT0iMCIgd2lkdGg9IjE1IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMTA2NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMzUwIiB5PSIwIiB3aWR0aD0iMTciIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4xMjQiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMzc0IiB5PSIwIiB3aWR0aD0iNSIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjAyIiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjM4NyIgeT0iMCIgd2lkdGg9IjYiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wMjg2NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iNDA0IiB5PSIwIiB3aWR0aD0iOSIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgLz48L3N2Zz4='
    ];
    return $images[mt_rand(0, count($images) - 1)];
}
