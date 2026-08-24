Feature:
    I want to make sure that a visitor can calculate the footprint of their trip to a property without logging in

    Scenario: A visitor submits the form and sees the emissions of their trip
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given property "777" is located at "40.4680", "-3.6160"
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given overnight stay "Hotel" with id "O1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc"
        Then I am redirected to route "public_carbon_footprint_property_trip_trip"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the response status should be 200
        Then I see a text "Calculadora de huella de tu viaje"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:yes|originPlaceReference:sevilla|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:yes|nights:2" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then the response status should be 200
        Then I see a text "Tu huella de carbono"
        Then I see a text "kg CO₂eq"
        Then trip calculation link "TL1" should have 1 trip calculations

    Scenario: A local visitor does not answer the trip questions
        Given account "MyCompany" with id "666" exists
        Given property "Museo A" with id "777" of account "666" exists
        Given property "777" is located at "40.4680", "-3.6160"
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given overnight stay "Hotel" with id "O1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the response status should be 200
        Then I see a text "¿Te has desplazado desde otra ciudad?"
        Then I see a text "¿Dormiste alguna noche fuera de tu casa?"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:yes|nights:2" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then the response status should be 200
        Then I see a text "No te has desplazado desde otra ciudad"
        Then I see a text "Noches declaradas: 2"
        Then trip calculation link "TL1" should have 1 trip calculations

    Scenario: Without a places provider the guest has to type the kilometres of every leg
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given the places provider is disabled
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the response status should be 200
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:120|roundTrip:no|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then trip calculation link "TL1" should have 1 trip calculations

    Scenario: A guest gets the places matching the query with a valid token
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/places?query=sev&kind=city"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "sevilla", "label": "Sevilla, España"}]
        """

    Scenario: Place suggestions are rejected with an invalid token
        When I go to "/es/carbon-footprint/property-trip/this-token-does-not-exist/places?query=sev&kind=city"
        Then the response status should be 404

    Scenario: An invalid token shows the invalid page
        When I go to "/es/carbon-footprint/property-trip/this-token-does-not-exist"
        Then the response status should be 404
        Then I see a text "Este enlace no es válido"

    Scenario: A calculator limited to overnight stays does not ask about the trip
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given overnight stay "Hotel" with id "O1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" with scope "overnight_stay" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the response status should be 200
        Then I do not see a text "¿Te has desplazado desde otra ciudad?"
        Then I do not see a text "¿Dormiste alguna noche fuera de tu casa?"
        Then I see a text "Nº de noches"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "nights:2" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then the response status should be 200
        Then I see a text "Noches declaradas: 2"
        Then I do not see a text "Desplazamientos"
        Then trip calculation link "TL1" should have 1 trip calculations

    Scenario: A calculator limited to transport does not ask about the overnight stays
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" with scope "transport" exists
        Given the places provider is disabled
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the response status should be 200
        Then I do not see a text "¿Te has desplazado desde otra ciudad?"
        Then I do not see a text "¿Dormiste alguna noche fuera de tu casa?"
        Then I see a text "¿Desde dónde viajaste?"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "originPlaceReference:Sevilla|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:120|roundTrip:no|trips:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then the response status should be 200
        Then I see a text "Desplazamientos"
        Then I do not see a text "Pernoctaciones"
        Then trip calculation link "TL1" should have 1 trip calculations
