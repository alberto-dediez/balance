Feature:
    I want to make sure that the system handles the "Asistentes" step of the carbon footprint wizard correctly

    Scenario: Accessing the attendees step without the providers step completed redirects back to providers
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/attendees"
        Then I am redirected to route "events_carbon_footprint_providers"

    Scenario: The attendees step shows the responses counter and the surveyed percentage
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event "333" has 100 estimated attendees
        Given event carbon footprint with id "999" for event "333" of account "666" with providers answered exists
        Given mobility "Coche" with id "M1" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey response with id "R1" for attendee survey "AS1" using mobility "M1" exists
        Given attendee survey response with id "R2" for attendee survey "AS1" using mobility "M1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/attendees"
        Then the response status should be 200
        Then I see a text "Respuestas recibidas"
        Then I see a text "2.0 %"
        Then attendee survey "AS1" should have 2 responses

    Scenario: Closing the survey with responses transitions to results and generates mobility/overnight activities
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with providers answered exists
        Given mobility "Coche" with id "M1" and emission factor for year "2026" exists
        Given overnight stay "Hotel" with id "OS1" and emission factor for year "2026" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey response with id "R1" for attendee survey "AS1" using mobility "M1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/attendees"
        When I submit the attendees close form with 100 real attendees and confidence level 95
        Then I am redirected to route "events_carbon_footprint_results"
        Then attendee survey for event "333" should be closed
        Then event carbon footprint "999" should have 1 attendees mobility activities in celebration
        Then event carbon footprint "999" should have 1 attendees overnight stay activities in celebration

    Scenario: Closing the survey with 0 responses keeps the existing manual attendees activities
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with providers answered exists
        Given mobility "Coche" with id "M1" and emission factor for year "2026" exists
        Given event carbon footprint "999" has a manual attendees mobility activity using mobility "M1"
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/attendees"
        Then I see a text "La encuesta no tiene respuestas todavía"
        When I submit the attendees close form with 50 real attendees and confidence level 90
        Then I am redirected to route "events_carbon_footprint_results"
        Then event carbon footprint "999" should have 1 attendees mobility activities in celebration
        Then event carbon footprint "999" should have 0 attendees overnight stay activities in celebration

    Scenario: Accessing results without the attendees step completed redirects back to attendees
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then I am redirected to route "events_carbon_footprint_attendees"
