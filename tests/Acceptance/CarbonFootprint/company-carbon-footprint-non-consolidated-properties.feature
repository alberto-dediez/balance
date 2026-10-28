Feature:
    I want the franchised properties and the properties leased out to third parties of a company to be calculated in its scope 3

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel Propio" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property "Hotel Franquicia" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given property "2222" has operating regime "franchised"
        Given property "Hotel Arrendado" with id "3333" of account "666" exists
        Given property "3333" has company "C1" assigned
        Given property "3333" has operating regime "leased_out"
        Given combustible "Gas natural" with id "CB1" and emission factor for year "2025" exists

    Scenario: The scope 1 step does not show the block of a franchised property
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Hotel Propio"
        Then I do not see a text "Hotel Franquicia"
        Then I do not see a text "Hotel Arrendado"

    Scenario: The scope 3 step shows the franchise with the scope 1 and 2 emissions of its completed property footprint
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "Propiedades franquiciadas"
        Then I see a text "Propiedades arrendadas a terceros"
        Then I see a text "Huella 2025 completada"
        Then I see a text "3.000,00"
        Then I see a text "Sin datos"

    Scenario: A franchise whose property footprint is not completed has no data
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "Sin datos"
        Then I do not see a text "Huella 2025 completada"
        Then I do not see a text "3.000,00"

    Scenario: The manual emissions of a property leased out to third parties are applied
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "4500" for property "3333"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then company carbon footprint "999" should have "4500" kg in source "downstream_leased_assets"
        Then company carbon footprint "999" should have "0" kg in source "franchises"

    Scenario: Excluding the franchises leaves them out of the total
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the scope "scope_3" step form excluding source "franchises" with reason "not_significant" and comment "Poco relevante"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then company carbon footprint "999" should have "0" kg in source "franchises"

    Scenario: Saving the scope 1 step removes the activities of a property that became franchised
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given company carbon footprint "999" has a combustible "CB1" activity of "600" kg for property "2222"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I submit the company scope "scope_1" step form with a combustible "CB1" of 100 "kg" for property "1111"
        Then I am redirected to route "company_carbon_footprints_scope_2"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "2222"

    Scenario: Reopening the property footprint after completing the company footprint does not change its total
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "company_carbon_footprints_results"
        Then company carbon footprint "999" should be completed
        Then company carbon footprint "999" should have total emissions of "3000" kg
        When I go to "/es/property-carbon-footprints/P2/step/results"
        When I submit a form with "" and button "Reabrir cálculo"
        Then property carbon footprint "P2" should not be completed
        Then company carbon footprint "999" should have total emissions of "3000" kg
        Then company carbon footprint "999" should have "3000" kg in source "franchises"

    Scenario: The mitigation plan step shows the franchise as an emission source with its property
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        Given mitigation action "Eficiencia en franquicias" with id "A1" for company carbon footprint "999" of account "666" with target year "2030" exists
        Given mitigation action "A1" reduces source "franchises" item "2222" by "0.2"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "" for property "2222"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I submit the providers step form
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then the response status should be 200
        Then I see a text "Franquicias"
        Then I see a text "Hotel Franquicia"
        Then I see a text "3.000,00"
        Then I see a text "2.400,00"

    Scenario: Saving the scope 3 step again updates the manual emissions of a franchise
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "4500" for property "2222"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then company carbon footprint "999" should have "4500" kg in source "franchises"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "5000" for property "2222"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then company carbon footprint "999" should have "5000" kg in source "franchises"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "" for property "2222"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then company carbon footprint "999" should have "3000" kg in source "franchises"

    Scenario: Completing the company footprint after the property footprint is completed refreshes the franchise
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "" for property "2222"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I submit the providers step form
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have "0" kg in source "franchises"
        Given property carbon footprint "P2" is completed
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "company_carbon_footprints_results"
        Then company carbon footprint "999" should be completed
        Then company carbon footprint "999" should have "3000" kg in source "franchises"

    Scenario: Changing the reason of an excluded source keeps a single exclusion
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the scope "scope_3" step form excluding source "franchises" with reason "not_significant" and comment "Poco relevante"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the scope "scope_3" step form excluding source "franchises" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then company carbon footprint "999" should have "0" kg in source "franchises"
