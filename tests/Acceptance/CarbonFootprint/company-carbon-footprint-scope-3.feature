Feature:
    I want to fill in the scope 3 step of a company carbon footprint by property or exclude the whole scope

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given purchase service "Limpieza" with id "PS1" and emission factor for year "2025" exists

    Scenario: The scope 3 step shows one block per property plus the unassigned one
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "Sin asignar a propiedad"
        Then I see a text "Hotel"
        Then I see a text "No calcular el alcance 3"

    Scenario: A purchase introduced in a property block is attributed to that property
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with a purchase service "PS1" of 1000 "EUR" for property "1111"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have status "scope_3_answered"
        Then company carbon footprint "999" should have 1 activities of source "purchased_goods_and_services" in scope "scope_3" for property "1111"
        Then company carbon footprint "999" should have 0 activities of source "purchased_goods_and_services" in scope "scope_3" for property "none"

    Scenario: A purchase introduced in the unassigned block stays unassigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the company scope 3 step form with a purchase service "PS1" of 1000 "EUR" for property "none"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have 1 activities of source "purchased_goods_and_services" in scope "scope_3" for property "none"

    Scenario: Excluding the whole scope 3 removes the activities of every property
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        Given company carbon footprint "999" has a purchase service activity "PS1" in scope 3 for property "1111"
        Given company carbon footprint "999" has a purchase service activity "PS1" in scope 3
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have scope 3 excluded with reason "no_data_available"
        Then company carbon footprint "999" should have 0 activities in scope 3
