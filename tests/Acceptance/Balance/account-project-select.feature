Feature:
    Ability for owners to select a project for their account

    Scenario: As an owner I can select a project
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/select"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha adherido el proyecto Item 1 a su cuenta"

    Scenario: Selecting an already adhered project is idempotent
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given project "1111" is adhered to account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/select"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Item 1"

    Scenario: As an owner without an account I can't select a project
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        Given project "Item 1" with id "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/select"
        Then the response status should be 403
