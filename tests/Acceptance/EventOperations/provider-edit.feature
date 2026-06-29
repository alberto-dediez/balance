Feature:
    I want to make sure that the system allow to edit a provider

    Scenario: As an owner, I can edit my own providers
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given provider "Proveedor A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/providers/edit/333"
        When I submit a form with "name:Nombre Actualizado" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Proveedor actualizado con éxito"

    Scenario: As an owner, I can't edit providers from other accounts
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given provider "Proveedor Ajeno" with id "333" of account "777" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/providers/edit/333"
        Then the response status should be 403

    Scenario: As a user I can't edit providers
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given provider "Proveedor A" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/providers/edit/333"
        Then the response status should be 403
