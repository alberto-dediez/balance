Feature:
        Ability to list incidences in web

        Scenario: As owner I can access a list of my incidences
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given incidence "Item 1" with id "1111" of account "mycompany" exists
                Given incidence "Item 2" with id "2222" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/incidences"
                Then the response status should be 200
                Then I see a table with 3 rows

        Scenario: As normal user I can't access a list of incidences
                Given account "MyCompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/incidences"
                Then the response status should be 403
