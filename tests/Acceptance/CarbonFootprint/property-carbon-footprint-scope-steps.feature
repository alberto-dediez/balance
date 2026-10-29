Feature:
    I want to fill in the scope 1 and scope 2 steps of a property carbon footprint

    Scenario: The scope 1 step shows its three emission sources
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given combustible "Gas natural" with id "C1" and emission factor for year "2025" exists
        Given transport "Camión" with id "RT1" and emission factor for year "2025" exists
        Given refrigerant gas "R-410A" with id "RG1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Instalaciones"
        Then I see a text "Desplazamientos en vehículos"
        Then I see a text "Fugas de equipos de refrigeración"

    Scenario: Excluding a source in scope 1 persists the exclusion and moves to scope 2
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given combustible "Gas natural" with id "C1" and emission factor for year "2025" exists
        Given transport "Camión" with id "RT1" and emission factor for year "2025" exists
        Given refrigerant gas "R-410A" with id "RG1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        When I submit the scope "scope_1" step form excluding source "refrigerant_leaks" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_2"
        Then property carbon footprint "999" should have source "refrigerant_leaks" excluded in scope "scope_1"
        Then property carbon footprint "999" should have 0 activities of source "refrigerant_leaks" in scope "scope_1"

    Scenario: The scope 2 step redirects to scope 1 when scope 1 has not been answered
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        Then I am redirected to route "property_carbon_footprints_scope_1"

    Scenario: The results step is available once scope 2 has been answered
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Totales"
        Then I see a text "Emisiones por alcance"

    Scenario: The results step redirects to the active step before scope 2 is answered
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then I am redirected to route "property_carbon_footprints_scope_2"

    Scenario: An owner of another account cannot open the wizard
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 403

    Scenario: Saving scope 1 with a combustible stores its well-to-tank emissions
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        Given combustible "Gas natural" with id "C1" and emission factor with well-to-tank for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        When I submit the scope "scope_1" step form with a combustible "C1" of 1000 "kg"
        Then I am redirected to route "property_carbon_footprints_scope_2"
        Then property carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1"
        Then the combustible activity of property carbon footprint "999" has well-to-tank emissions of 610 kg co2eq

    Scenario: Saving scope 2 with a marketer and a country mix stores both well-to-tank emissions
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" is in country "C-ES"
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor with well-to-tank for year "2025" exists
        Given electricity mix for country "C-ES" with id "MIX1" and emission factor with well-to-tank for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        Then the response status should be 200
        When I submit the scope "scope_2" step form with an electricity marketer "EM1" of 1000 "kWh"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then the electricity activity of property carbon footprint "999" has well-to-tank emissions of 610 kg co2eq
        Then the electricity activity of property carbon footprint "999" has location-based well-to-tank emissions of 610 kg co2eq

    Scenario: The results step shows the scope 2 dual reporting
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" is in country "C-ES"
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        Given electricity mix for country "C-ES" with id "MIX1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form with an electricity marketer "EM1" of 1000 "kWh"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Alcance 2 · método de localización (kg CO₂eq)"
        Then I see a text "1.500,00"
        Then I see a text "Total · método de localización (kg CO₂eq)"
        Then I do not see a text "sin factor de mix eléctrico"

    Scenario: The results step warns about the electricity without an electricity mix
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" is in country "C-ES"
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form with an electricity marketer "EM1" of 1000 "kWh"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Actividades de electricidad sin factor de mix eléctrico para su país y año: 1."
