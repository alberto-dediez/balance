Feature:
    I want to fill in the scope 3 step of a property carbon footprint or exclude the whole scope

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists

    Scenario: The scope 3 step redirects to scope 2 when scope 2 has not been answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        Then I am redirected to route "property_carbon_footprints_scope_2"

    Scenario: The scope 3 step shows its upstream and downstream categories
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "No calcular el alcance 3"
        Then I see a text "Aguas arriba"
        Then I see a text "Aguas abajo"
        Then I see a text "Bienes y servicios adquiridos"
        Then I see a text "Inversiones"
        Then I do not see a text "Cargar consumos de Bioscore"

    Scenario: Saving scope 3 with a purchase in euros stores the activity
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given purchase service "Limpieza" with id "PS1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form with a purchase service "PS1" of 1000 "EUR"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then property carbon footprint "999" should have status "scope_3_answered"
        Then property carbon footprint "999" should have 1 activities in scope 3
        Then property carbon footprint "999" should have 1 activities of source "purchased_goods_and_services" in scope "scope_3"

    Scenario: Saving an investment in a currency without exchange rate shows the error
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given economic sector "Hostelería" with id "ES1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form with an investment "ES1" of 1000 "USD"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        Then I see a text "No se han podido guardar las actividades"
        Then property carbon footprint "999" should have status "scope_2_answered"

    Scenario: Excluding the whole scope 3 answers the step and starts the mitigation plan
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should have status "employees_answered"
        Then property carbon footprint "999" should have scope 3 excluded with reason "no_data_available"
        Then property carbon footprint "999" should have 0 activities in scope 3
        Then I see a text "Medidas de Reducción y Mitigación"

    Scenario: Excluding the whole scope 3 removes its previous activities
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given property carbon footprint "999" has a purchase service activity "PS1" in scope 3
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should have 0 activities in scope 3

    Scenario: A business trip stores a journey and a stay in the same source
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given mobility "Avión" with id "M1" and emission factor for year "2025" exists
        Given overnight stay "Hotel" with id "OS1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form with a business trip "M1" of 500 km and a stay "OS1" of 2 nights for 3 people
        Then I am redirected to route "property_carbon_footprints_providers"
        Then property carbon footprint "999" should have 2 activities of source "business_travel" in scope "scope_3"

    Scenario: Excluding the business travel category removes both of its sections
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given mobility "Avión" with id "M1" and emission factor for year "2025" exists
        Given overnight stay "Hotel" with id "OS1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form with a business trip "M1" of 500 km and a stay "OS1" of 2 nights for 3 people
        Then I am redirected to route "property_carbon_footprints_providers"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope "scope_3" step form excluding source "business_travel" with reason "not_applicable" and comment "Sin viajes"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then property carbon footprint "999" should have source "business_travel" excluded in scope "scope_3"
        Then property carbon footprint "999" should have 0 activities of source "business_travel" in scope "scope_3"

    Scenario: The results of a footprint with scope 3 excluded show the whole scope exclusion
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 excluded exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Alcance 3"
        Then I see a text "Alcance completo"
        Then I see a text "Sin datos disponibles"

    Scenario: The mitigation plan step redirects to scope 3 when scope 3 has not been answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "property_carbon_footprints_scope_3"
