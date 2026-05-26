Feature:
    Ability to list wastes in web

    Scenario: As admin I can access a list of all wastes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given waste "Madera" with id "1111" exists
        Given waste "Plastico" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/wastes"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all wastes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given waste "Madera" with id "1111" exists
        Given waste "Plastico" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/wastes"
        Then the response status should be 403

    Scenario: As an admin, I can list all wastes via API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given waste "Madera" with id "1111" exists
        Given waste "Plastico" with id "2222" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a GET request to "/api/es/wastes"
        Then the status code should be 200

    Scenario: Not authenticated user cannot list wastes via API
        When I send a GET request to "/api/es/wastes"
        Then the status code should be 403

    Scenario: As a regular user, I cannot list wastes via API
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am authenticated in API as "user@dediez.software"
        And I send a GET request to "/api/es/wastes"
        Then the status code should be 403