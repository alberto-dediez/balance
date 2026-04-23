Feature:
        Ability to list daily wastes in web

        Scenario: As user I can't access daily wastes
                Given account "MyCompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
                Given daily waste for day "2025-06-04" with id "d1" of account "mycompany" exists
                Given daily waste for day "2025-06-05" with id "d2" of account "mycompany" exists
                Given daily waste for day "2025-06-06" with id "d3" of account "mycompany" exists
                Given daily waste for day "2025-06-07" with id "d4" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/daily-wastes"
                Then the response status should be 403

        Scenario: As owner I can access a list of all daily wastes of my account
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given daily waste for day "2025-06-04" with id "d1" of account "mycompany" exists
                Given daily waste for day "2025-06-05" with id "d2" of account "mycompany" exists
                Given daily waste for day "2025-06-06" with id "d3" of account "mycompany" exists
                Given daily waste for day "2025-06-07" with id "d4" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/daily-wastes"
                Then the response status should be 200
                Then I see a table with 5 rows