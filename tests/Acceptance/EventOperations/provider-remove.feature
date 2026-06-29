Feature:
    I want to make sure that the system allow to remove a provider

    Scenario: As an owner, I can remove my own providers
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given provider "Proveedor A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/providers/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el proveedor Proveedor A"

    Scenario: As normal user I can't remove providers
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given provider "Proveedor A" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/providers/remove/333"
        Then the response status should be 403
