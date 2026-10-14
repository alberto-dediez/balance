Feature:
    I want to make sure that the software allow to register a company

    Scenario: As an admin, I can access to register new companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/companies/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/companies/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/companies/register"
        When I submit a form with "name:Test Company|vatNumber:B12345678" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Empresa creada correctamente: Test Company"

    Scenario: As an owner, I can register new companies for my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/companies/register"
        When I submit a form with "name:Mi Empresa|vatNumber:B12345678" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Empresa creada correctamente: Mi Empresa"
