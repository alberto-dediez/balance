Feature:
    I want to define the reduction and mitigation measures of a company carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel Sol" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property "Hotel Luna" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        Given combustible "Gas natural" with id "CB1" and emission factor for year "2025" exists

    Scenario: The step aggregates the emissions of the properties by element
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "999" has a combustible "CB1" activity of "600" kg for property "1111"
        Given company carbon footprint "999" has a combustible "CB1" activity of "300" kg for property "2222"
        Given company carbon footprint "999" has a combustible "CB1" activity of "100" kg
        Given mitigation action "Caldera de condensación" with id "A1" for company carbon footprint "999" of account "666" with target year "2030" exists
        Given mitigation action "A1" reduces source "fixed_facilities" item "CB1" by "0.2"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 200
        Then I see a text "Caldera de condensación"
        Then I see a text "1.000,00"
        Then I see a text "800,00"

    Scenario: Saving scope 3 for the first time imports the measures of the previous year of the company
        Given company carbon footprint with id "998" for company "C1" of account "666" and year "2024" with scope 2 answered exists
        Given mitigation action "Caldera de condensación" with id "A1" for company carbon footprint "998" of account "666" with target year "2030" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "company_carbon_footprints_materiality"
        When I skip the materiality step
        Then I am redirected to route "company_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have 1 mitigation actions
        Then I see a text "Caldera de condensación"

    Scenario: Next completes the company footprint
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "company_carbon_footprints_results"
        Then company carbon footprint "999" should be completed

    Scenario: Previous in the mitigation plan step leads to the employees step
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 200
        Then the previous step link should point to "/es/company-carbon-footprints/999/step/employees"

    Scenario: An owner registers a manual measure on a franchised property
        Given property "2222" has operating regime "franchised"
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with materiality answered exists
        Given mitigation action "Eficiencia en franquicias" with id "A1" for company carbon footprint "999" of account "666" with target year "2030" exists
        Given mitigation action "A1" reduces source "franchises" item "2222" by "0.1"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "" for property "2222"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I submit the providers step form
        Then I am redirected to route "company_carbon_footprints_employees"
        When I submit the employees step form with employees "" and total "1"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        When I go to "/es/mitigation-actions/A1/edit"
        Then the response status should be 200
        Then I see a text "Hotel Luna"
        When I fill the unprefixed field "update_mitigation_action[effects][0][emissionSource]" with "franchises|2222"
        When I submit a form with "[effects][0][reduction]:20" and button "Guardar"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then the only mitigation action of company carbon footprint "999" should reduce source "franchises" item "2222" by "0.2"

    Scenario: Two franchised properties with the same name are both offered in the selector
        Given property "Hotel Luna" with id "3333" of account "666" exists
        Given property "3333" has company "C1" assigned
        Given property "2222" has operating regime "franchised"
        Given property "3333" has operating regime "franchised"
        Given property carbon footprint with id "P2" for property "2222" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "P2" has a combustible "CB1" activity of "3000" kg with "100" kg well-to-tank
        Given property carbon footprint "P2" is completed
        Given property carbon footprint with id "P3" for property "3333" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "P3" has a combustible "CB1" activity of "2000" kg with "100" kg well-to-tank
        Given property carbon footprint "P3" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with materiality answered exists
        Given mitigation action "Eficiencia en franquicias" with id "A1" for company carbon footprint "999" of account "666" with target year "2030" exists
        Given mitigation action "A1" reduces source "franchises" item "2222" by "0.1"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with manual emissions "" for property "2222"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I submit the providers step form
        Then I am redirected to route "company_carbon_footprints_employees"
        When I submit the employees step form with employees "" and total "1"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        When I go to "/es/mitigation-actions/A1/edit"
        Then the response status should be 200
        Then I see a text 'value="franchises|2222"'
        Then I see a text 'value="franchises|3333"'
        Then I see a text "Hotel Luna (2)"
        When I fill the unprefixed field "update_mitigation_action[effects][0][emissionSource]" with "franchises|3333"
        When I submit a form with "[effects][0][reduction]:20" and button "Guardar"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then the only mitigation action of company carbon footprint "999" should reduce source "franchises" item "3333" by "0.2"
