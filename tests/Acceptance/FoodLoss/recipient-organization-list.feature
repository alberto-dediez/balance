Feature:
    Ability to list entidades receptoras in web

    Scenario: As an owner I can access a list of my entidades receptoras
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given recipient organization "Org 1" with id "1111" of account "mycompany" exists
        Given recipient organization "Org 2" with id "2222" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/recipient-organizations"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As an admin, I can not access the list of entidades receptoras
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/recipient-organizations"
        Then the response status should be 403

    Scenario: As a normal user, I can not access the list of entidades receptoras
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/recipient-organizations"
        Then the response status should be 403
