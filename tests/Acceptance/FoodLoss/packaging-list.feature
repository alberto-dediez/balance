Feature:
    Ability to list packagings in web

    Scenario: As owner I can access a list of my packagings
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given packaging "Envase 1" with id "1111" of account "mycompany" exists
        Given packaging "Envase 2" with id "2222" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/packagings"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of packagings
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/packagings"
        Then the response status should be 403

    Scenario: As admin I can't access a list of packagings
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/packagings"
        Then the response status should be 403
