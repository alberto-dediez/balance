Feature:
    I want to make sure that the system allow to remove a company

    Scenario: As an admin, I can remove companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given company "Empresa A" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/companies/remove/333" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la empresa Empresa A"

    Scenario: As normal user I can't remove companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given company "Empresa A" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/companies/remove/333" with csrf token "remove"
        Then the response status should be 403

    Scenario: As an owner, I can remove my own companies
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Empresa A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/companies/remove/333" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la empresa Empresa A"
