Feature:
    I want to make sure that the software allows registering an employee

    Scenario: As an admin, I can access the register employee page
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/employees/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As a normal user I can't register employees
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/employees/register"
        Then the response status should be 403

    Scenario: As an admin, I can register a new employee
        Given account "Test Account" with id "test-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "test-account" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/employees/register"
        When I submit a form with "name:John Doe|email:john@example.com|phone:+1234567890" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Employee created successfully: John Doe"
