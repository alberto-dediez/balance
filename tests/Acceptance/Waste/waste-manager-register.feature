Feature:
    I want to make sure that the software allow to register a waste manager

    Scenario: As normal user I can't register new waste managers
        Given account "MyCompany" with id "my-company" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-company" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/waste-managers/register"
        Then the response status should be 403

    Scenario: As an owner, I can register new waste managers for my account
        Given account "MyCompany" with id "my-company" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-company" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/waste-managers/register"
        When I submit a form with "name:Hotel|isPublic:1" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Gestor de residuos creado correctamente"