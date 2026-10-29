Feature:
    I want to make sure that the carbon footprint report can be reissued and retried

    Scenario: The event results page offers reissuing the report and shows the compensation summary
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given event carbon footprint "999" is completed
        Given event carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        Given account "666" compensated "2.5" tonnes of event "333"
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I see a text "Compensación de emisiones"
        Then I see a text "Reemitir informe"
        When I click on link "Reemitir informe"
        Then I am redirected to route "events_carbon_footprint_results"
        Then the report of event carbon footprint "999" should be "generating_texts" keeping its file path

    Scenario: The event results page offers retrying the generation when the report failed
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given event carbon footprint "999" is completed
        Given event carbon footprint "999" has a report "failed" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I see a text "No se ha podido generar el informe de esta huella de carbono: boom"
        Then I see a text "Reintentar generación"
        When I click on link "Reintentar generación"
        Then I am redirected to route "events_carbon_footprint_results"
        Then the report of event carbon footprint "999" should be "generating_texts"

    Scenario: The property results page offers reissuing the report
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Reemitir informe"
        Then I see a text "Descargar informe"
        When I click on link "Reemitir informe"
        Then I am redirected to route "property_carbon_footprints_results"
        Then the report of property carbon footprint "999" should be "generating_texts" keeping its file path

    Scenario: Reissuing without csrf token is rejected
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint-reports/999/reissue"
        Then the response status should be 403
        Then the report of property carbon footprint "999" should be "generated"
