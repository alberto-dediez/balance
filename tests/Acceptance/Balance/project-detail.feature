Feature:
    Ability for owners to see a project detail

    Scenario: As an owner I can see the project detail
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111"
        Then the response status should be 200
        Then I see a text "Item 1"

    Scenario: As an owner I see the adhesion status on the project detail
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given project "1111" is adhered to account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111"
        Then the response status should be 200
        Then I see a text "Retirar de mi cuenta"

    Scenario: As an owner without an account I can't access the project detail
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        Given project "Item 1" with id "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111"
        Then the response status should be 403
