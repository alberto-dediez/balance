Feature:
    Ability to list projects in web

    Scenario: As admin I can access a list of all projects
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given project "Item 1" with id "1111" exists
        Given project "Item 2" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/projects"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all projects
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given project "Item 1" with id "1111" exists
        Given project "Item 2" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/projects"
        Then the response status should be 403

    Scenario: As owner of an account without compensation I can't access the projects
        Given account "MyCompany" with id "666" exists
        Given account "666" has feature "compensation" disabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects"
        Then the response status should be 403
        When I go to "/es/projects/1111/select"
        Then the response status should be 403
