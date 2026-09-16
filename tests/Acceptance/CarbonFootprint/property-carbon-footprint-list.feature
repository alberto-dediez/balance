Feature:
    I want to list the carbon footprints of my properties

    Scenario: The list only shows the footprints of the current account
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "Hostal" with id "2222" of account "666" exists
        Given property "Ajena" with id "3333" of account "777" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given property carbon footprint with id "998" for property "2222" of account "666" and year "2024" exists
        Given property carbon footprint with id "997" for property "3333" of account "777" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: The list can be filtered by year
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "Hostal" with id "2222" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given property carbon footprint with id "998" for property "2222" of account "666" and year "2024" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints"
        When I submit a form with "year:2024" and button "list_property_carbon_footprints[filter]"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: The properties list offers the carbon footprint action
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties"
        Then the response status should be 200
        Then I see a text "Cálculo de Huella de Carbono"
