Feature:
    Ability to export the detail of a company carbon footprint as spreadsheet files

    Scenario: As an owner I can export the detail of a completed footprint of my account
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "CCF1" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "CCF1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/company_carbon_footprint_detail/export?companyCarbonFootprintId=CCF1"
        Then the response status should be 200
        Then I see a form field "[filter][companyCarbonFootprintId]" with value "CCF1"
        When I submit a form with "[filter][companyCarbonFootprintId]:CCF1" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: As an owner I cannot export a footprint that is not completed
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "CCF2" for company "C1" of account "666" and year "2024" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company_carbon_footprint_detail/export"
        Then the response status should be 200
        Then I do not see an option "CCF2" in form field "[filter][companyCarbonFootprintId]"
        When I pick "CCF2" in the autocomplete "companyCarbonFootprintId"
        When I submit a form with "[filter][companyCarbonFootprintId]:CCF2" and button "Exportar"
        Then the response status should be 200
        Then I do not see a text "La exportación se está generando"

    Scenario: As a normal user I cannot access the company carbon footprint detail export
        Given account "Mi cuenta" with id "666" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/company_carbon_footprint_detail/export"
        Then the response status should be 403

    Scenario: The wizard offers the export only for a completed footprint
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "CCF1" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "CCF1" is completed
        Given company carbon footprint with id "CCF2" for company "C1" of account "666" and year "2024" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/CCF1/step/results"
        Then the response status should be 200
        Then I see a link to "/es/company_carbon_footprint_detail/export?companyCarbonFootprintId=CCF1"
        When I go to "/es/company-carbon-footprints/CCF2/step/scope-1"
        Then the response status should be 200
        Then I do not see a link with name "Exportar"
