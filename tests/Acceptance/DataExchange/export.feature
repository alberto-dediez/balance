Feature:
    Ability to export entities as spreadsheet files

    Scenario: As admin I can access the export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/users/export"
        Then the response status should be 200

    Scenario: As a normal user I cannot access the export screen
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/users/export"
        Then the response status should be 403

    Scenario: An unknown export type returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/unknown/export"
        Then the response status should be 404
