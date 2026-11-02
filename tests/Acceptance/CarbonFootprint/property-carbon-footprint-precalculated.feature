Feature:
    I want to enter the scope 1 and scope 2 results of a property carbon footprint that is already calculated

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists

    Scenario: Registering a footprint with the already calculated option marks it as precalculated
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/register"
        Then the response status should be 200
        When I tick the checkbox "register_property_carbon_footprint[precalculated]"
        When I submit a form with "propertyId:1111|year:2025" and button "Registrar"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then property carbon footprint for property "1111" and year "2025" should be precalculated

    Scenario: The scope 1 step of a precalculated footprint asks for the reported emissions of each source
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Huella ya calculada"
        Then I see a text "Concepto"
        Then I see a text "Introducir datos por actividades"
        Then I do not see a text "Cargar consumos de Bioscore"

    Scenario: Saving a reported emission in scope 1 stores it in its source
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I submit the scope step form with reported emissions "Caldera" of "2" "t" for source "fixed_facilities"
        Then I am redirected to route "property_carbon_footprints_scope_2"
        Then property carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1"
        Then property carbon footprint "999" should have "2000" kg in source "fixed_facilities"

    Scenario: Activity sections sent to a precalculated footprint are ignored
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" precalculated exists
        Given combustible "Gas natural" with id "C1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I submit the scope "scope_1" step form with a combustible "C1" of "1000" "kWh"
        Then I am redirected to route "property_carbon_footprints_scope_2"
        Then property carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1"

    Scenario: The scope 3 step of a precalculated footprint accepts reported emissions in the fuel and energy related category
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I submit the scope step form with reported emissions "Caldera" of "2" "t" for source "fixed_facilities"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope step form with reported emissions "Electricidad" of "500" "kg" for source "building_electricity"
        Then I am redirected to route "property_carbon_footprints_materiality"
        When I skip the materiality step
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "Actividades relacionadas con combustibles y energía"
        Then I see a text "Concepto"
        When I submit the scope step form with reported emissions "WTT gas" of "300" "kg" for source "fuel_and_energy_related"
        Then property carbon footprint "999" should have 1 activities of source "fuel_and_energy_related" in scope "scope_3"
        Then property carbon footprint "999" should have "300" kg in source "fuel_and_energy_related"

    Scenario: Going back to activities removes the reported emissions and unmarks the footprint
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I submit the scope step form with reported emissions "Caldera" of "2" "t" for source "fixed_facilities"
        Then property carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1"
        When I go to "/es/property-carbon-footprints/999/precalculated-mode/0" with csrf token "remove"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then property carbon footprint "999" should not be precalculated
        Then property carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1"

    Scenario: The precalculated mode cannot be changed in a completed footprint
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is precalculated
        Given property carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I do not see a link with name "Introducir datos por actividades"
        Then I do not see a text "Para volver a desglosar actividades"
        When I go to "/es/property-carbon-footprints/999/precalculated-mode/0" with csrf token "remove"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "La huella está completada. Reábrela para modificarla."
        Then property carbon footprint "999" should be precalculated

    Scenario: The results of a precalculated footprint do not declare a location based scope 2 figure
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is precalculated
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Alcance 2 · método de localización (kg CO₂eq)"
        Then I see a text ">---</dd>"

    Scenario: The results of a footprint that is not precalculated declare its location based scope 2 figure
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I do not see a text ">---</dd>"

    Scenario: Bioscore consumptions cannot be loaded into a precalculated footprint
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        When I load the Bioscore consumptions of property carbon footprint "999" for scope "scope_1"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then I see a text "No se han podido cargar los consumos: No se pueden cargar consumos de Bioscore en una huella ya calculada"

    Scenario: A footprint that is not precalculated keeps the activity tables and the Bioscore action
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Instalaciones"
        Then I see a text "Cargar consumos de Bioscore"
        Then I see a text "Huella ya calculada"
        Then I do not see a text "Introducir datos por actividades"

