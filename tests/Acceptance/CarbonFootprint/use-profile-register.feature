Feature:
    I want to make sure that the software allow to register a use profile

    Scenario: As an admin, I can access to register new use profiles
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/use-profiles/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new use profiles
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/use-profiles/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new use profiles
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/use-profiles/register"
        When I submit a form with "name:Lavadora clase A|unit:ud" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Perfil de uso creado correctamente: Lavadora clase A"

    Scenario: After register a new use profile, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/use-profiles/register"
        When I submit a form with "name:Lavadora clase A|unit:ud" and button "Registrar"
        Then I am redirected to route "admin_use_profiles_edit"
