Feature:
    Ability to list companies in web

    Scenario: As admin I can access a list of all companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given company "Empresa A" with id "1111" exists
        Given company "Empresa B" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/companies"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all companies
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given company "Empresa A" with id "1111" exists
        Given company "Empresa B" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/companies"
        Then the response status should be 403

    Scenario: As owner I can access a list of companies of my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Empresa A" with id "1111" of account "666" exists
        Given company "Empresa B" with id "2222" of account "666" exists
        Given company "Otra Empresa" with id "3333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/companies"
        Then the response status should be 200
        Then I see a table with 4 rows
