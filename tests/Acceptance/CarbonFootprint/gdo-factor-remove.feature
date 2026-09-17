Feature:
    I want to make sure that the system is allow to remove an gdo factor

    Scenario: As an admin, I can access to remove gdo factors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given gdo factor "Factor 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/gdo-factors/remove/333" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el factor GdO Factor 1"

    Scenario: As normal user I can't remove gdo factors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given gdo factor "Factor 1" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/gdo-factors/remove/333" with csrf token "remove"
        Then the response status should be 403
