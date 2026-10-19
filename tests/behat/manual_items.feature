@block @block_teacher_checklist
Feature: Teachers manage manual items in the Teacher Checklist
  In order to track tasks that the automatic scan cannot detect
  As a teacher
  I need to add manual items and see them in the dashboard and in the course block

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

  Scenario: A teacher adds a manual item and sees it in the dashboard
    Given I am on the "C1" "block_teacher_checklist > dashboard" page logged in as "teacher1"
    When I set the field "New Manual Task" to "Print the syllabus"
    And I press "Add"
    Then I should see "Task added successfully!"
    And I should see "Print the syllabus"

  Scenario: A manual item added in the dashboard is listed in the course block
    Given I am on the "C1" "block_teacher_checklist > dashboard" page logged in as "teacher1"
    And I set the field "New Manual Task" to "Print the syllabus"
    And I press "Add"
    When I am on "Course 1" course homepage
    Then "Print the syllabus" "text" should exist in the "Teacher Checklist" "block"

  Scenario: Markup typed into a manual item is shown as plain text
    Given I am on the "C1" "block_teacher_checklist > dashboard" page logged in as "teacher1"
    When I set the field "New Manual Task" to "<b>Bold</b> reminder"
    And I press "Add"
    Then I should see "Bold reminder"
    And I should not see "<b>"
