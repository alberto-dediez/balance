Feature:
    Ability to list employees in web

    Scenario: As admin I can access a list of all employees
        Given account "Test Account" with id "test-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "test-account" exists
        Given employee "John Doe" with email "john@example.com" and id "1111" exists
        Given employee "Jane Doe" with email "jane@example.com" and id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/employees"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access the admin employee list
        Given account "Test Account" with id "test-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "test-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given employee "John Doe" with email "john@example.com" and id "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/employees"
        Then the response status should be 403
