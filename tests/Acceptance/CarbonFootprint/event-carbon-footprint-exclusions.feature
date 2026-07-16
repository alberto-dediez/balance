Feature:
    I want to make sure that emission source exclusions in the carbon footprint wizard work correctly

    Scenario: Excluding a source in the assembly step persists the exclusion and removes its activities
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with materials answered exists
        Given waste "Residuo" with id "W1" and emission factor for year "2026" exists
        Given event carbon footprint "999" has a waste activity using waste "W1" in assembly
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/assembly"
        Then the response status should be 200
        Then I see a text "Excluir esta fuente de emisión"
        When I submit the "assembly" step form excluding source "waste_management" with reason "no_data_available" and comment "Sin facturas"
        Then I am redirected to route "events_carbon_footprint_celebration"
        Then event carbon footprint "999" should have source "waste_management" excluded in phase "assembly" with reason "no_data_available"
        Then event carbon footprint "999" should have 0 activities of source "waste_management" in phase "assembly"
        When I go to "/es/events/333/carbon-footprint/step/assembly"
        Then the response status should be 200
        Then I see a text "Sin facturas"

    Scenario: The results page shows the excluded emission sources section
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given event carbon footprint "999" has source "waste_management" excluded in phase "assembly" with reason "no_data_available"
        Given event carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I see a text "Fuentes de emisión excluidas"
        Then I see a text "Sin datos disponibles"
