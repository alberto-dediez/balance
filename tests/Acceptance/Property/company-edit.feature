Feature:
    I want to make sure that the system allow to edit a company

    Scenario: As an admin, I can access to edit companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given company "Empresa A" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/companies/edit/333"
        When I submit a form with "name:Nuevo Nombre" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Empresa actualizada con éxito"

    Scenario: As a user I can't edit companies from admin
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given company "Empresa A" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/companies/edit/333"
        Then the response status should be 403

    Scenario: As an owner, I can edit my own companies
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Empresa A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/companies/edit/333"
        When I submit a form with "name:Nombre Actualizado" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Empresa actualizada con éxito"

    Scenario: As an owner, I can't edit companies from other accounts
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Empresa Ajena" with id "333" of account "777" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/companies/edit/333"
        Then the response status should be 403
