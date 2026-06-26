Feature:
    Ability to list road transports in web

    Scenario: As admin I can access a list of all road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given road transport "Road Transport 1" with id "1111" exists
        Given road transport "Road Transport 2" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/road-transports"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given road transport "Road Transport 1" with id "1111" exists
        Given road transport "Road Transport 2" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/road-transports"
        Then the response status should be 403