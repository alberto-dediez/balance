Feature:
        Ability to list countries in web

        Scenario: As admin I can access a list of all countries
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given country "Spain" with id "es" exists
                Given country "Portugal" with id "pt" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/admin/countries"
                Then the response status should be 200
                Then I see a table with 4 rows

        Scenario: As normal user I can't access a list of all countries
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given user "user1@dediez.software" with role "ROLE_USER" exists
                Given country "Spain" with id "es" exists
                Given country "Portugal" with id "pt" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/admin/countries"
                Then the response status should be 403