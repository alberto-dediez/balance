Feature:
    I want to make sure that the software allow to register a refrigerant gas

    Scenario: As an admin, I can access to register new refrigerant gases
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/refrigerant-gases/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new refrigerant gases
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/refrigerant-gases/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new refrigerant gases
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/refrigerant-gases/register"
        When I submit a form with "name:Test Refrigerant Gas" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Gas refrigerante creado correctamente: Test Refrigerant Gas"

    Scenario: After register a new refrigerant gas, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/refrigerant-gases/register"
        When I submit a form with "name:Test Refrigerant Gas" and button "Registrar"
        Then I am redirected to route "admin_refrigerant_gases_edit"
