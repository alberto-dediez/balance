Feature:
    I want to make sure that the system is allow to remove an electricity mix

    Scenario: As an admin, I can access to remove electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given electricity mix for country "C-ES" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el mix eléctrico España"

    Scenario: As normal user I can't remove electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given electricity mix for country "C-ES" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/electricity-mixes/remove/333"
        Then the response status should be 403
