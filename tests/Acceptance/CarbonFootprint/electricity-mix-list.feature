Feature:
    Ability to list electricity mixes in web

    Scenario: As admin I can access a list of all electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given country "Francia" with iso "FR" and id "C-FR" exists
        Given electricity mix for country "C-ES" with id "1111" exists
        Given electricity mix for country "C-FR" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes"
        Then the response status should be 200
        Then I see a table with 4 rows
        Then I see a text "España"

    Scenario: As normal user I can't access a list of all electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given electricity mix for country "C-ES" with id "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/electricity-mixes"
        Then the response status should be 403
