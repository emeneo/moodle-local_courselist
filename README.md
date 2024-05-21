(1) Summary
The plugin makes the following possibe: Creation of unlimited alternative course list pages (course catalogs, course menus), based on custom course fields (checkbox).
Developed for Moodle Version: 4.1

(2) Installation
The plugin can be installed by either uploading it via the moodle upload interface or manually by placing the unzipped package into the folder /local/

(3) Create course list categories and add courses (Admin)
1.	Go to Site Administration --> Courses --> Course custom fields
URL: /course/customfield.php 
2.	Klick on “Add a new category”
3.	 Change the name of the newly created category (in the following we will use “S+ course categories”, then click on “Add new custom field” (Type: “Checkbox”)
4.	In the popup window enter:
•	Field name 
•	Short name
•	Description 
       Under common custom course fields settings, choose
•	Locked=YES
•	Visible to = Nobody 
Repeat for all categories needed. 
  
(4) Create new course list page (Admin)
1.	First go to Site Administration  Plugins  Local Plugins  Manage course list
        /local/courselist/manage.php)
2.	Click on “Add new list”
 
3.	Enter:
•	Start- and end date of course visibility
•	Course list name 
•	Description
Finally select related custom course field categories.  Here we will select “S+ course categories”. 
 
You can visit the course list page by clicking on its name
 
(5) Let Courses appear in the Course list (Teacher, Admin)
1 Go to the settings of the related course.
 
2 Select where your course should appear 
 
Important:
In order for the max enrolments to appear on the course list page, the enrolment method “waitlist enrolment” or “student enrolment” has to be at the top of enrolment methods. 
(6) Remarks
1 The Free seats work with the enroll plugins 
•	Waitlist
•	Self

The name of the plugin is currently “courselist” and hence the URL /local/courselist/…. After pilot run (and paralell usage of the old “local/course_search” we can change the name to this (or anything else.

