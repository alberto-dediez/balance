Feature:
    I want a company carbon footprint to be completed when its mitigation plan step is submitted and to be reopened on demand

    Scenario: Saving scope 2 moves to the mitigation plan without completing
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should not be completed

    Scenario: Scope 1 of a completed footprint is shown without the save button
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "company_carbon_footprints_results"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Sin asignar a propiedad"
        Then I do not see a text "Siguiente"
        Then I do not see a text "Cargar consumos de Bioscore"

    Scenario: Posting scope 1 of a completed footprint redirects with a warning
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "company_carbon_footprints_results"
        When I make a POST request to "/es/company-carbon-footprints/999/step/scope-1"
        Then I am redirected to route "company_carbon_footprints_results"
        Then I see a text "La huella está completada. Reábrela para modificarla."
        Then company carbon footprint "999" should be completed

    Scenario: Reopening the calculation allows editing again
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "company_carbon_footprints_results"
        Then I see a text "Reabrir cálculo"
        When I submit a form with "" and button "Reabrir cálculo"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then company carbon footprint "999" should not be completed
        Then I see a text "Siguiente"

    Scenario: Completing the footprint registers its report
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "company_carbon_footprints_results"
        Then the report of company carbon footprint "999" should be "generating_texts"
        Then I see a text "Se están generando los textos del informe, te notificaremos cuando estén listos."

    Scenario: Reopening the calculation removes the generated report
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        Given company carbon footprint "999" is completed
        Given company carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        When I submit a form with "" and button "Reabrir cálculo"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then company carbon footprint "999" should not be completed
        Then there should be no report of company carbon footprint "999"
