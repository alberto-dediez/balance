Feature:
    I want to make sure that the software allow to register a surplus

    Scenario: As an owner, I can access to register new surpluses
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin, I can't register new surpluses
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/surpluses/register"
        Then the response status should be 403

    Scenario: As an owner, I can register a surplus
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given service category "Desayuno" with id "service-cat-1" exists
        Given critical point "Cocina" with id "critical-point-1" of account "my-account" exists
        Given surplus cause "Exceso de producción" with id "surplus-cause-1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/register"
        When I submit a form with "date:2026-05-21|serviceCategoryId:service-cat-1|criticalPointId:critical-point-1|surplusCauseId:surplus-cause-1|foodType:vegetables|quantity:5|unit:kg" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Excedente registrado correctamente: 21/05/2026"
