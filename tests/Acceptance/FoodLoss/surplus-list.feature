Feature:
    Ability to list surpluses in web

    Scenario: As an owner, I can access a list of my surpluses
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        Given surplus with id "surplus-2" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: As an admin, I can't access the surplus list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/surpluses"
        Then the response status should be 403
