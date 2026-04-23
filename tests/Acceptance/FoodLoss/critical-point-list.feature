Feature:
        Ability to list critical points in web

        Scenario: As owner I can access a list of all my critical points
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given property "My Property" with id "myprop" of account "mycompany" exists
                Given critical point "Item 1" with id "1111" of account "mycompany" and property "myprop" exists
                Given critical point "Item 2" with id "2222" of account "mycompany" and property "myprop" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/critical-points"
                Then the response status should be 200
                Then I see a table with 4 rows

        Scenario: As normal user I can't access a list of critical points
                Given user "user1@dediez.software" with role "ROLE_USER" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/critical-points"
                Then the response status should be 403

        Scenario: As admin I can't access a list of critical points
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/critical-points"
                Then the response status should be 403
