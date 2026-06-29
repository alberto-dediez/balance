Feature:
    Ability to list events in web

    Scenario: As owner I can access a list of events of my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "1111" of account "666" exists
        Given event "Evento B" with id "2222" of account "666" exists
        Given event "Otro Evento" with id "3333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of events
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given event "Evento A" with id "1111" exists
        Given event "Evento B" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/events"
        Then the response status should be 403
