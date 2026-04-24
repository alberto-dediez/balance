Feature:
    I want to make sure that the system allows removing employees

    Scenario: As an admin, I can remove an employee
        Given account "Test Account" with id "test-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "test-account" exists
        Given employee "John Doe" with email "john@example.com" and id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/employees/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Employee John Doe has been deleted"

    Scenario: As normal user I can't remove employees
        Given account "Test Account" with id "test-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "test-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given employee "John Doe" with email "john@example.com" and id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/employees/remove/333"
        Then the response status should be 403
