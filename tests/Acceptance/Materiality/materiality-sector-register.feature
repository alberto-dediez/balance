Feature:
    I want to make sure that the software allows to register a materiality sector

    Scenario: As an admin, I can access to register new materiality sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new materiality sectors
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-sectors/register"
        Then the response status should be 403

    Scenario: As an admin, I can register a new materiality sector and I am redirected to the list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors/register"
        When I submit a form with "name:Hoteles" and button "Registrar"
        Then I am redirected to route "admin_materiality_sectors"
        Then the response status should be 200
        Then I see a text "Sector de materialidad creado correctamente: Hoteles"
        Then there should be 1 materiality sectors

    Scenario: As an admin, I can't register a materiality sector without name
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors/register"
        When I submit a form with "name:" and button "Registrar"
        Then the response status should be 200
        Then there should be 0 materiality sectors
