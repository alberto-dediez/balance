Feature:
    I want to make sure that the software allow to register an emission factor group

    Scenario: As an admin, I can access to register new emission factor groups
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emission-factor-groups/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new emission factor groups
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/emission-factor-groups/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new emission factor groups
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emission-factor-groups/register"
        When I submit a form with "name:AR5|gwpCo2:1|gwpCh4:28|gwpN2O:265" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Grupo de coeficientes creado correctamente: AR5"
