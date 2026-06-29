Feature:
    Ability to list gdo factors in web

    Scenario: As admin I can access a list of all gdo factors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given gdo factor "Factor 1" with id "1111" exists
        Given gdo factor "Factor 2" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/gdo-factors"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all gdo factors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given gdo factor "Factor 1" with id "1111" exists
        Given gdo factor "Factor 2" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/gdo-factors"
        Then the response status should be 403
