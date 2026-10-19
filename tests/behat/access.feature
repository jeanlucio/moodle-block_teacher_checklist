@block @block_teacher_checklist
Feature: Only teachers can see the Teacher Checklist
  In order to keep the checklist private to the people who manage the course
  As a student
  I must not reach the checklist dashboard or see the block

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 0                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "blocks" exist:
      | blockname         | contextlevel | reference | pagetypepattern | defaultregion |
      | teacher_checklist | Course       | C1        | course-view-*   | side-pre      |

  Scenario: A student cannot open the dashboard
    When I am on the "C1" "block_teacher_checklist > dashboard" page logged in as "student1"
    Then I should see "Sorry, but you do not currently have permissions to do that (Update course settings)"

  Scenario: A student does not see the block in the course
    When I am on the "Course 1" "course" page logged in as "student1"
    Then I should not see "Teacher Checklist"

  Scenario: A teacher sees the block in the course
    When I am on the "Course 1" "course" page logged in as "teacher1"
    Then "Teacher Checklist" "block" should exist
