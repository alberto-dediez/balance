Feature:
    Ability for owners to deselect a project from their account

    Scenario: As an owner I can deselect a project
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given project "1111" is adhered to account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/deselect"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha retirado el proyecto Item 1 de su cuenta"

    Scenario: As an owner belonging to another account I can't deselect a project not adhered to my account
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given project "Item 1" with id "1111" exists
        Given project "1111" is adhered to account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/deselect"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No se ha podido retirar el proyecto Item 1"
