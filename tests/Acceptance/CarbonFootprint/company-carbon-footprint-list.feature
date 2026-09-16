Feature:
    I want to list the carbon footprints of my companies

    Scenario: The list only shows the footprints of the current account
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company "Hostelera" with id "C2" of account "666" exists
        Given company "Ajena" with id "C3" of account "777" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given company carbon footprint with id "998" for company "C2" of account "666" and year "2024" exists
        Given company carbon footprint with id "997" for company "C3" of account "777" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: The list can be filtered by year
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company "Hostelera" with id "C2" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given company carbon footprint with id "998" for company "C2" of account "666" and year "2024" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints"
        When I submit a form with "year:2024" and button "list_company_carbon_footprints[filter]"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: The companies list offers the carbon footprint action
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/companies"
        Then the response status should be 200
        Then I see a text "Cálculo de Huella de Carbono"
