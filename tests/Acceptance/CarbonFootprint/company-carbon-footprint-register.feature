Feature:
    I want to register a carbon footprint for a company

    Scenario: An owner registers a carbon footprint for a company of their account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/register"
        Then the response status should be 200
        When I submit a form with "companyId:C1|year:2025" and button "Registrar"
        Then I follow redirect
        Then I see a text "Cálculo de Huella de Carbono creado correctamente"

    Scenario: Registering twice for the same company and year fails
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/register"
        Then the response status should be 200
        When I submit a form with "companyId:C1|year:2025" and button "Registrar"
        Then I see a text "Ya existe una huella de carbono para esta empresa y año"

    Scenario: A company of another account is not offered in the selector
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Ajena" with id "C9" of account "777" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/register"
        Then the response status should be 200
        Then I do not see a text "Ajena"

    Scenario: A plain user cannot register a company carbon footprint
        Given account "MyCompany" with id "666" exists
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/company-carbon-footprints/register"
        Then the response status should be 403
