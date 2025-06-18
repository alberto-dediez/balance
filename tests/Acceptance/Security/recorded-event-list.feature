Feature:
    Ability to list recorded events in web

    Scenario: As admin I can access a list of all recorded events
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/events"
        Then the response status should be 200
        Then I see a table with 1 rows

    Scenario: As normal user I can't access a list of all recorded events
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/events"
        Then the response status should be 403