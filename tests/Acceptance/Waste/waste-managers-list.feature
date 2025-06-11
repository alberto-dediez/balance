Feature:
    Ability to list waste managers in web

    Scenario: As normal user I can't access a list of all waste managers
        Given account "MyCompany" with id "my-company" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-company" exists
        Given waste manager "Manager" with id "manager" of account "my-company" exists
        Given waste manager "Other" with id "other" of account "my-company" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/waste-managers"
        Then the response status should be 403

    Scenario: As owner I can access a list of all waste managers of my account
        Given account "MyCompany" with id "my-company" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-company" exists
        Given waste manager "Manager" with id "manager" of account "my-company" exists
        Given waste manager "Other" with id "other" of account "my-company" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/waste-managers"
        Then the response status should be 200
        Then I see a table with 3 rows