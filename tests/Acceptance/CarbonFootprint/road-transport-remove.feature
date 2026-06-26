Feature:
    I want to make sure that the system is allow to remove a road transport

    Scenario: As an admin, I can access to remove road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given road transport "Road Transport 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/road-transports/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el transporte Road Transport 1"

    Scenario: As normal user I can't remove road transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given road transport "Road Transport 1" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/road-transports/remove/333"
        Then the response status should be 403