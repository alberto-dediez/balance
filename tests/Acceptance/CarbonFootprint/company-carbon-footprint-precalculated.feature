Feature:
    I want to enter the scope 1 and scope 2 results of a company carbon footprint that is already calculated

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned

    Scenario: Registering a footprint with the already calculated option marks it as precalculated
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/register"
        Then the response status should be 200
        When I tick the checkbox "register_company_carbon_footprint[precalculated]"
        When I submit a form with "companyId:C1|year:2025" and button "Registrar"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then company carbon footprint for company "C1" and year "2025" should be precalculated

    Scenario: Reported emissions are saved for the consolidated property and for the unassigned block
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Sin asignar a propiedad"
        Then I see a text "Concepto"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I submit the scope step form with reported emissions "Caldera hotel" of "800" "kg" for source "fixed_facilities" for property "1111"
        Then I am redirected to route "company_carbon_footprints_scope_2"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I submit the scope step form with reported emissions "Caldera oficinas" of "2" "t" for source "fixed_facilities" for property "none"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "none"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
        Then company carbon footprint "999" should have "2000" kg in source "fixed_facilities"

    Scenario: Reported emissions of the consolidated property and of the unassigned block are saved in the same submission
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I submit the scope step form with reported emissions "Caldera hotel" of "800" "kg" for source "fixed_facilities" for property "1111" and "Caldera oficinas" of "2" "t" unassigned
        Then I am redirected to route "company_carbon_footprints_scope_2"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "none"
        Then company carbon footprint "999" should have "2800" kg in source "fixed_facilities"

    Scenario: The results of a precalculated footprint do not declare a location based scope 2 figure
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "999" is precalculated
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Alcance 2 · método de localización (kg CO₂eq)"
        Then I see a text ">---</dd>"

    Scenario: Going back to activities removes the reported emissions and unmarks the footprint
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I submit the scope step form with reported emissions "Caldera oficinas" of "2" "t" for source "fixed_facilities" for property "none"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "none"
        When I go to "/es/company-carbon-footprints/999/precalculated-mode/0" with csrf token "remove"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then company carbon footprint "999" should not be precalculated
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "none"
