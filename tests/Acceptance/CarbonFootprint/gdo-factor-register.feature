Feature:
    I want to make sure that the software allow to register an gdo factor

    Scenario: As an admin, I can access to register new gdo factors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/gdo-factors/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new gdo factors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/gdo-factors/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new gdo factors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/gdo-factors/register"
        When I submit a form with "name:Test GdO Factor" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Factor GdO creado correctamente: Test GdO Factor"

    Scenario: After register a new gdo factor, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/gdo-factors/register"
        When I submit a form with "name:Test GdO Factor" and button "Registrar"
        Then I am redirected to route "admin_gdo_factors_edit"
