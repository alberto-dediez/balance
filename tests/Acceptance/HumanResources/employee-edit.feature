Feature:
    I want to make sure that the system allows accessing and editing employee details

    Scenario: As an admin, I can access and edit an employee
        Given account "Test Account" with id "test-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "test-account" exists
        Given employee "John Doe" with email "john@example.com" and id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/employees/edit/333"
        When I submit a form with "name:Jane Doe|email:jane@example.com" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Employee updated successfully"

    Scenario: As a normal user I can't edit employees
        Given account "Test Account" with id "test-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "test-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given employee "John Doe" with email "john@example.com" and id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/employees/edit/333"
        Then the response status should be 403

    Scenario: As an owner, I can't access the admin edit page
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given employee "John Doe" with email "john@example.com" and id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/employees/edit/333"
        Then the response status should be 403
