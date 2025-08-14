Feature:
    Ability to see details of daily wastes in web

    Scenario: As user I can't access daily waste details
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
        Given daily waste for day "2025-06-04" with id "d1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/daily-wastes/view/d1"
        Then the response status should be 403

    Scenario: As owner I can access all daily wastes details of my account
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given daily waste for day "2025-06-04" with id "d1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/daily-wastes/view/d1"
        Then the response status should be 200