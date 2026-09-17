Feature:
    I want to make sure that the system is allow to remove a destination

    Scenario: As an admin, I can access to remove destinations
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given destination "Barcelona" with id "123" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/destinations/remove/123" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el destino"

    Scenario: As normal user I can't remove destinations
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given destination "Barcelona" with id "123" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/destinations/remove/123" with csrf token "remove"
        Then the response status should be 403