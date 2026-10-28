Feature:
    I want a property carbon footprint to be completed when its mitigation plan step is submitted and to be reopened on demand

    Scenario: Saving scopes 2 and 3 moves to the mitigation plan without completing
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should not be completed

    Scenario: Scope 1 of a completed footprint is shown without the save button
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Instalaciones"
        Then I do not see a text "Siguiente"
        Then I do not see a text "Cargar consumos de Bioscore"

    Scenario: Posting scope 1 of a completed footprint redirects with a warning
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        When I make a POST request to "/es/property-carbon-footprints/999/step/scope-1"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "La huella está completada. Reábrela para modificarla."
        Then property carbon footprint "999" should be completed

    Scenario: Reopening the calculation allows editing again
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "Reabrir cálculo"
        When I submit a form with "" and button "Reabrir cálculo"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then property carbon footprint "999" should not be completed
        Then I see a text "Siguiente"

    Scenario: Completing the footprint registers its report
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        Then the report of property carbon footprint "999" should be "generating_texts"
        Then I see a text "Se están generando los textos del informe, te notificaremos cuando estén listos."

    Scenario: Reopening the calculation removes the generated report
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        When I submit a form with "" and button "Reabrir cálculo"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then property carbon footprint "999" should not be completed
        Then there should be no report of property carbon footprint "999"

    Scenario: The footprint cannot be completed until scope 3 is answered
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I make a POST request to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        Then property carbon footprint "999" should not be completed

    Scenario: The total includes the scope 3 purchases and the derived fuel and energy emissions
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "999" has a combustible "CB1" activity of "1000" kg with "210" kg well-to-tank
        Given property carbon footprint "999" has a purchase service activity "PS1" in scope 3
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        Then property carbon footprint "999" should be completed
        Then property carbon footprint "999" should have total emissions of "1710" kg
