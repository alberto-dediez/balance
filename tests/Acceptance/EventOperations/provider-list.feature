Feature:
    Ability to list providers in web

    Scenario: As owner I can access a list of providers of my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given provider "Proveedor A" with id "1111" of account "666" exists
        Given provider "Proveedor B" with id "2222" of account "666" exists
        Given provider "Otro Proveedor" with id "3333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/providers"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of providers
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given provider "Proveedor A" with id "1111" exists
        Given provider "Proveedor B" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/providers"
        Then the response status should be 403
