Feature:
    I want to make sure that the software allow to register a economic sector

    Scenario: As an admin, I can access to register new economic sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new economic sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/economic-sectors/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new economic sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/register"
        When I submit a form with "name:Fabricación de productos metálicos|classificationCode:25|unit:EUR" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Sector económico creado correctamente: Fabricación de productos metálicos"

    Scenario: After register a new economic sector, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/register"
        When I submit a form with "name:Fabricación de productos metálicos|classificationCode:25|unit:EUR" and button "Registrar"
        Then I am redirected to route "admin_economic_sectors_edit"

    Scenario: As an admin, I can't register an economic sector with a unit that is not a currency
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/register"
        When I pick "kg" in the autocomplete "unit"
        When I submit a form with "name:Fabricación de productos metálicos|unit:kg" and button "Registrar"
        Then the response status should be 200
        Then I see a form
        When I go to "/es/admin/economic-sectors"
        Then I do not see a text "Fabricación de productos metálicos"

    Scenario: As an admin, I can't register an economic sector with a classification code already in use
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given economic sector "Fabricación de productos metálicos" with id "333" and classification code "25" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors/register"
        When I submit a form with "name:Otro sector|classificationCode:25|unit:EUR" and button "Registrar"
        Then the response status should be 200
        Then I see a text "Ya existe un sector económico con ese código"
        When I go to "/es/admin/economic-sectors"
        Then I do not see a text "Otro sector"
