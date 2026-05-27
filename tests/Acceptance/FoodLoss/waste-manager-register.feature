Feature:
    I want to make sure that the software allows to register a gestor de residuos

    Scenario: As an owner, I can access to register new gestores de residuos
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/food-waste-managers/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin, I can not access to register new gestores de residuos
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/food-waste-managers/register"
        Then the response status should be 403

    Scenario: As a normal user, I can not register new gestores de residuos
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/food-waste-managers/register"
        Then the response status should be 403

    Scenario: As an owner, I can register new gestores de residuos
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/food-waste-managers/register"
        When I submit a form with "name:Gestor Test" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Gestor de residuos creado correctamente: Gestor Test"
