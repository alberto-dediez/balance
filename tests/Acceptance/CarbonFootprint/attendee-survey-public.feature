Feature:
    I want to make sure that an attendee can answer the public attendee survey of an event without logging in

    Scenario: An attendee can complete the 2-step survey and a response is registered
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_intercity"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        Then the response status should be 200
        Then I see a text "Movilidad interurbana"
        When I submit a form with "cameFromAnotherCity:yes|days:2|mobilityId:M1|vehicleOccupants:1|distanceKm:100|usedHotel:yes|hotelNights:2" and button "Continuar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_city"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        Then the response status should be 200
        When I submit a form with "days:1|mobilityId:M1|vehicleOccupants:1|distanceKm:5" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_thanks"
        Then I see a text "Gracias"
        Then attendee survey "AS1" should have 1 responses

    Scenario: A closed survey shows the closed page and refuses new responses
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" is closed with 100 real attendees and confidence level 95
        When I go to "/es/carbon-footprint/attendee-survey/token-abc"
        Then the response status should be 409
        Then I see a text "Esta encuesta ya se ha cerrado"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        Then the response status should be 409
        Then attendee survey "AS1" should have 0 responses

    Scenario: An invalid token shows the invalid page
        When I go to "/es/carbon-footprint/attendee-survey/this-token-does-not-exist"
        Then the response status should be 404
        Then I see a text "Este enlace no es válido"
