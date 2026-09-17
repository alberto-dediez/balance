Feature:
    Ability to load the Bioscore consumptions into a property carbon footprint scope

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given combustible "Gas natural" with id "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" and emission factor for year "2025" exists
        Given road transport "Camión" with id "bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb" and emission factor for year "2025" exists
        Given refrigerant gas "R-410A" with id "99999999-9999-9999-9999-999999999999" and emission factor for year "2025" exists
        Given Bioscore catalog item "7" of catalog "facility_fuel" named "Gas Natural" with id "BI1" exists
        Given Bioscore catalog item "BI1" is mapped to "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa"
        Given Bioscore catalog item "9" of catalog "refrigerant_gas" named "R-410A" with id "BI2" exists
        Given Bioscore catalog item "BI2" is mapped to "99999999-9999-9999-9999-999999999999"

    Scenario: Loading scope 1 replaces the activities of every source with the Bioscore consumptions
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given Bioscore returns for property "1111" in "2025" a "facility_fuel" consumption of "1000" "kg" of item "7" named "Gas Natural"
        Given Bioscore returns for property "1111" in "2025" a "refrigerant_gas" consumption of "3" "kg" of item "9" named "R-410A"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar consumos de Bioscore"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then I see a text "Consumos de Bioscore cargados"
        Then property carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1"
        Then property carbon footprint "999" should have 1 activities of source "refrigerant_leaks" in scope "scope_1"
        Then property carbon footprint "999" should have 0 activities of source "own_vehicles" in scope "scope_1"
        Then Bioscore was asked for consumptions of account "666" and property "1111" in "2025"

    Scenario: An unmapped item is skipped and reported while the rest is loaded
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given Bioscore catalog item "8" of catalog "transport_fuel" named "Gasolina" with id "BI3" exists
        Given Bioscore returns for property "1111" in "2025" a "transport_fuel" consumption of "200" "l" of item "8" named "Gasolina"
        Given Bioscore returns for property "1111" in "2025" a "refrigerant_gas" consumption of "3" "kg" of item "9" named "R-410A"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar consumos de Bioscore"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then I see a text "Consumos de Bioscore omitidos"
        Then I see a text "Gasolina (#8"
        Then property carbon footprint "999" should have 1 activities of source "refrigerant_leaks" in scope "scope_1"
        Then property carbon footprint "999" should have 0 activities of source "own_vehicles" in scope "scope_1"

    Scenario: Loading scope 2 creates one electricity activity per marketer
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Endesa" with id "88888888-8888-8888-8888-888888888888" and emission factor for year "2025" exists
        Given Bioscore catalog item "3" of catalog "electricity_marketer" named "Endesa" with id "BI4" exists
        Given Bioscore catalog item "BI4" is mapped to "88888888-8888-8888-8888-888888888888"
        Given Bioscore returns for property "1111" in "2025" a "electricity" consumption of "10000" "kWh" of item "3" named "Endesa"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I click on link "Cargar consumos de Bioscore"
        Then I am redirected to route "property_carbon_footprints_scope_2"
        Then I see a text "Consumos de Bioscore cargados"
        Then property carbon footprint "999" should have 1 activities of source "building_electricity" in scope "scope_2"

    Scenario: When Bioscore does not resolve the property nothing changes
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar consumos de Bioscore"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then I see a text "Ninguna propiedad de esta huella está vinculada a Bioscore"
        Then property carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1"

    Scenario: When Bioscore is unavailable an error is shown
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given Bioscore consumptions are unavailable
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar consumos de Bioscore"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then I see a text "No se ha podido conectar con Bioscore"

    Scenario: Without a valid token the load is refused
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope_1/bioscore"
        Then the response status should be 403

    Scenario: An owner of another account cannot load the consumptions
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope_1/bioscore"
        Then the response status should be 403
