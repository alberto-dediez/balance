Feature:
    I want to make sure that the software allow to register a provider

    Scenario: As an owner, I can access to register new providers
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/providers/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new providers
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/providers/register"
        Then the response status should be 403

    Scenario: As an owner, I can register new providers for my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/providers/register"
        When I submit a form with "name:Mi Proveedor|vatNumber:B12345678|email:proveedor@example.com" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Proveedor creado correctamente: Mi Proveedor"
