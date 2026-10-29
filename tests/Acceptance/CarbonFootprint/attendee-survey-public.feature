Feature:
    I want to make sure that an attendee can answer the public attendee survey of an event without logging in

    Scenario: An attendee can complete the 2-step survey and a response is registered
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and travel mode "driving" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        Given place "hotel" named "Hotel Madrid" at "40.4200", "-3.7000" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_intercity"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        Then the response status should be 200
        Then I see a text "Movilidad interurbana"
        When I submit a form with "cameFromAnotherCity:yes|originPlaceReference:sevilla|usedHotel:yes|accommodationPlaceReference:hotel|hotelNights:2|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|roundTrip:yes" and button "Continuar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_city"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        Then the response status should be 200
        Then I see a text "para calcular y agregar la huella de carbono del evento"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:yes|originPlaceReference:hotel|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|roundTrip:yes|trips:3" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_thanks"
        Then I see a text "Gracias"
        Then attendee survey "AS1" should have 1 responses

    Scenario: An attendee sees their individual footprint after answering the survey
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given overnight stay "Hotel" with id "O1" and emission factor for the current year exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        Given place "hotel" named "Hotel Madrid" at "40.4200", "-3.7000" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|originPlaceReference:sevilla|usedHotel:yes|accommodationPlaceReference:hotel|hotelNights:2|hotelPeople:3|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"
        Then the response status should be 200
        Then I see a text "Tu huella de carbono"
        Then I see a text "kg CO₂eq"
        Then I see a text "Noches declaradas: 2 · Personas alojadas: 3"
        Then attendee survey "AS1" should have 1 responses

    Scenario: An attendee can declare a multimodal trip with a plane and a train
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Avión" with id "M1" and travel mode "flight" exists
        Given mobility "Tren" with id "M2" and travel mode "transit" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        Then the response status should be 200
        When I submit a form with "cameFromAnotherCity:yes|originPlaceReference:sevilla|usedHotel:no|multimodal:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][destinationPlaceReference]:madrid|[legs][0][manualDistanceKm]:400|[legs][1][mobilityId]:M2|[legs][1][vehicleOccupants]:1|[legs][1][manualDistanceKm]:30|roundTrip:yes|trips:1" and button "Continuar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_city"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_thanks"
        Then attendee survey "AS1" should have 1 responses

    Scenario: An attendee that did not move within the city only answers the first block
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and travel mode "driving" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|originPlaceReference:sevilla|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|roundTrip:no|trips:2" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_thanks"
        Then attendee survey "AS1" should have 1 responses

    Scenario: Without a places provider the attendee has to type the kilometres of every leg
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given the places provider is disabled
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:120|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_thanks"
        Then attendee survey "AS1" should have 1 responses

    Scenario: Place suggestions are rejected with an invalid token
        When I go to "/es/carbon-footprint/attendee-survey/this-token-does-not-exist/places?query=sev&kind=city"
        Then the response status should be 404

    Scenario: An attendee gets the places matching the query with a valid token
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/places?query=sev&kind=city"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "sevilla", "label": "Sevilla, España"}]
        """

    Scenario: Place suggestions with a query shorter than 3 characters return no results
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/places?query=se&kind=city"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        []
        """

    Scenario: Place suggestions are rejected when the survey is closed
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" is closed with 100 real attendees and confidence level 95
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/places?query=sev&kind=city"
        Then the response status should be 409

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
