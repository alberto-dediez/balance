Feature:
    I want to go through the providers step of a company carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned

    Scenario: Saving scope 3 leads to the providers step
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        Given purchase service "Limpieza" with id "PS1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with a purchase service "PS1" of 1000 "EUR" for property "1111"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then the response status should be 200
        Then I see a text "Aportaciones de proveedores"
        Then the wizard should show the step "Proveedores"
        Then company carbon footprint "999" should have status "scope_3_answered"

    Scenario: Excluding the whole scope 3 skips the providers step
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then the wizard should not show the step "Proveedores"
        Then company carbon footprint "999" should have status "providers_answered"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"

    Scenario: The providers step redirects to scope 3 when scope 3 has not been answered
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        Then I am redirected to route "company_carbon_footprints_scope_3"

    Scenario: A footprint with scope 3 excluded before the providers step existed is sent back to scope 3
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered and later excluded exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        Then I am redirected to route "company_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have status "providers_answered"

    Scenario: Next answers the providers step and leads to the mitigation plan
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        When I submit the providers step form
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have status "providers_answered"

    Scenario: The mitigation plan step redirects to the providers step when it has not been answered
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then I am redirected to route "company_carbon_footprints_providers"
        When I go to "/es/company-carbon-footprints/999/edit"
        Then I am redirected to route "company_carbon_footprints_providers"

    Scenario: The footprint cannot be completed until the providers step is answered
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "owner@dediez.software"
        When I make a POST request to "/es/company-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "company_carbon_footprints_providers"
        Then company carbon footprint "999" should not be completed

    Scenario: Posting the providers step of a completed footprint redirects with a warning
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        Given company carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        Then the response status should be 200
        Then I do not see a text "Siguiente"
        When I make a POST request to "/es/company-carbon-footprints/999/step/providers"
        Then I am redirected to route "company_carbon_footprints_results"
        Then I see a text "La huella está completada. Reábrela para modificarla."

    Scenario: A user with the User role cannot access the providers step
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "user@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        Then the response status should be 403
