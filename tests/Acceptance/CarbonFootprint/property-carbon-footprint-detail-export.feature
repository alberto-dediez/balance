Feature:
    Ability to export the detail of a property carbon footprint as spreadsheet files

    Scenario: As an owner I can export the detail of a completed footprint of my account
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "PCF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PCF1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property_carbon_footprint_detail/export?propertyCarbonFootprintId=PCF1"
        Then the response status should be 200
        Then I see a form field "[filter][propertyCarbonFootprintId]" with value "PCF1"
        When I submit a form with "[filter][propertyCarbonFootprintId]:PCF1" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: As an owner I cannot export a footprint that is not completed
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "PCF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PCF1" is completed
        Given property carbon footprint with id "PCF2" for property "1111" of account "666" and year "2024" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property_carbon_footprint_detail/export"
        Then the response status should be 200
        Then I see an option "PCF1" in form field "[filter][propertyCarbonFootprintId]"
        Then I do not see an option "PCF2" in form field "[filter][propertyCarbonFootprintId]"
        When I pick "PCF2" in the autocomplete "propertyCarbonFootprintId"
        When I submit a form with "[filter][propertyCarbonFootprintId]:PCF2" and button "Exportar"
        Then the response status should be 200
        Then I do not see a text "La exportación se está generando"

    Scenario: As an owner I cannot export a footprint of another account
        Given account "Mi cuenta" with id "666" exists
        Given account "Otra cuenta" with id "999" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Otro hotel" with id "2222" of account "999" exists
        Given property carbon footprint with id "PCF1" for property "2222" of account "999" and year "2025" with employees answered exists
        Given property carbon footprint "PCF1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property_carbon_footprint_detail/export"
        Then the response status should be 200
        Then I do not see an option "PCF1" in form field "[filter][propertyCarbonFootprintId]"
        When I pick "PCF1" in the autocomplete "propertyCarbonFootprintId"
        When I submit a form with "[filter][propertyCarbonFootprintId]:PCF1" and button "Exportar"
        Then the response status should be 200
        Then I do not see a text "La exportación se está generando"

    Scenario: As a normal user I cannot access the property carbon footprint detail export
        Given account "Mi cuenta" with id "666" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/property_carbon_footprint_detail/export"
        Then the response status should be 403

    Scenario: As an admin impersonating an account I can export the detail of its footprints
        Given account "Admins" with id "555" exists
        Given account "Mi cuenta" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "555" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "PCF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PCF1" is completed
        When I am logged as "admin@dediez.software"
        When I impersonate account "Mi cuenta"
        When I go to "/es/property_carbon_footprint_detail/export?propertyCarbonFootprintId=PCF1"
        Then the response status should be 200
        Then I see a form field "[filter][propertyCarbonFootprintId]" with value "PCF1"
        When I submit a form with "[filter][propertyCarbonFootprintId]:PCF1" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: The wizard offers the export only for a completed footprint
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "PCF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PCF1" is completed
        Given property carbon footprint with id "PCF2" for property "1111" of account "666" and year "2024" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/PCF1/step/results"
        Then the response status should be 200
        Then I see a link to "/es/property_carbon_footprint_detail/export?propertyCarbonFootprintId=PCF1"
        When I go to "/es/property-carbon-footprints/PCF2/step/scope-1"
        Then the response status should be 200
        Then I do not see a link with name "Exportar"
