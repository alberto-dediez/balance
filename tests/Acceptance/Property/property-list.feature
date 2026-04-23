Feature:
        Ability to list properties in web

        Scenario: As admin I can access a list of all properties
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given property "Hotel" with id "1111" exists
                Given property "Rural" with id "2222" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/admin/properties"
                Then the response status should be 200
                Then I see a table with 4 rows

        Scenario: As normal user I can't access a list of all properties
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given user "user1@dediez.software" with role "ROLE_USER" exists
                Given property "Hotel" with id "1111" exists
                Given property "Rural" with id "2222" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/admin/properties"
                Then the response status should be 403

        Scenario: As owner I can access a list of all properties of my account
                Given account "MyCompany" with id "666" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
                Given property "Hotel" with id "1111" of account "666" exists
                Given property "Rural" with id "2222" of account "666" exists
                Given property "Playa" with id "3333" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/properties"
                Then the response status should be 200
                Then I see a table with 4 rows