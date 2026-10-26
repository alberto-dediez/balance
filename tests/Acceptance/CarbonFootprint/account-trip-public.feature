Feature:
    I want to make sure that a visitor can calculate the footprint of any trip with the trip calculator of an account

    Background:
        Given account "Viajes Sostenibles" with id "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given overnight stay "Hotel" with id "O1" and emission factor for the current year exists
        Given account trip calculator with id "TL1" and token "token-abc" of account "666" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" in country "ES" and areas "Sevilla, Andalucía, España" exists
        Given place "valencia" named "Valencia, España" at "39.4699", "-0.3763" in country "ES" and areas "Valencia, Comunitat Valenciana, España" exists
        Given place "teruel" named "Teruel, España" at "40.3456", "-1.1065" in country "ES" and areas "Teruel, Aragón, España" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" in country "ES" and areas "Madrid, Comunidad de Madrid, España" exists
        Given place "paris" named "París, Francia" at "48.8566", "2.3522" in country "FR" and areas "París, Île-de-France, Francia" exists

    Scenario: A visitor with a free destination calculates the footprint of their trip
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        Then the response status should be 200
        Then I see a text "Viajes Sostenibles"
        Then I see a text "¿Cuál es el destino de tu viaje?"
        Then I see a text "¿Tu viaje incluye desplazamiento?"
        Then I see a text "¿Incluye noches de alojamiento?"
        Then I do not see a text "¿Te has desplazado desde otra ciudad?"
        Then I see a text "¿Hasta dónde llegas en este medio?"
        Then I do not see a text "¿Hasta dónde llegaste en este medio?"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:paris|cameFromAnotherCity:yes|originPlaceReference:sevilla|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:1800|roundTrip:yes|trips:1|overnight:yes|nights:2|people:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then the response status should be 200
        Then I see a text "Tu huella de carbono"
        Then I see a text "Destino: París, Francia"
        Then trip calculation of link "TL1" should have destination "París, Francia"

    Scenario: A visitor who does not say where they travel cannot calculate the footprint
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:yes|nights:2|people:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_trip"
        Then I see a text "Debes indicar el destino de tu viaje."
        Then trip calculation link "TL1" should have 0 trip calculations

    Scenario: A visitor sees the reason why the footprint cannot be calculated in their language
        When I go to "/en/carbon-footprint/trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:yes|nights:2|people:1" and button "Calculate my footprint"
        Then I am redirected to route "public_carbon_footprint_trip_trip"
        Then I see a text "You must enter the destination of your trip."
        Then I do not see a text "Debes indicar el destino de tu viaje."

    Scenario: A calculator limited to some countries rejects a destination abroad
        Given country "España" with iso "ESP" and id "C-ES" exists
        Given account trip calculator "TL1" allows destinations in countries "ESP"
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:paris|cameFromAnotherCity:no|overnight:yes|nights:1|people:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_trip"
        Then I see a text "El destino elegido no está permitido en esta calculadora."
        Then trip calculation link "TL1" should have 0 trip calculations
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:valencia|cameFromAnotherCity:no|overnight:yes|nights:1|people:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then I see a text "No has indicado ningún desplazamiento, así que tu viaje no genera emisiones de desplazamiento."
        Then I do not see a text "No te has desplazado desde otra ciudad"
        Then trip calculation of link "TL1" should have destination "Valencia, España"

    Scenario: A calculator limited to a region rejects a destination outside of it
        Given region "comunitat-valenciana" named "Comunitat Valenciana" with bounds "37.84", "-1.53", "40.79", "0.69" exists
        Given account trip calculator "TL1" allows destinations in region "comunitat-valenciana"
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:madrid|cameFromAnotherCity:no|overnight:yes|nights:1|people:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_trip"
        Then I see a text "El destino elegido no está permitido en esta calculadora."
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:teruel|cameFromAnotherCity:no|overnight:yes|nights:1|people:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_trip"
        Then I see a text "El destino elegido no está permitido en esta calculadora."
        Then trip calculation link "TL1" should have 0 trip calculations
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:valencia|cameFromAnotherCity:no|overnight:yes|nights:1|people:1" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then trip calculation of link "TL1" should have destination "Valencia, España"

    Scenario: A consultant offers its own properties and those of the accounts it manages
        Given account "Greenhost" with id "888" is a consultant
        Given account "Hotel asociado" with id "999" exists
        Given account "999" has consultant "888"
        Given account "Otra cadena" with id "555" exists
        Given account trip calculator with id "TL2" and token "token-consultant" of account "888" exists
        Given account trip calculator "TL2" allows destinations in all properties
        Given property "Hotel Greenhost" with id "P1" of account "888" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        Given property "Hotel Asociado" with id "P2" of account "999" exists
        Given property "P2" is located at "39.4800", "-0.3800"
        Given property "Hotel Ajeno" with id "P3" of account "555" exists
        Given property "P3" is located at "39.4900", "-0.3900"
        When I go to "/es/carbon-footprint/trip/token-consultant/destinations?query=Hotel"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "property:P2", "label": "Hotel Asociado"}, {"value": "property:P1", "label": "Hotel Greenhost"}]
        """

    Scenario: A calculator limited to some properties only suggests the selected ones
        Given property "Hotel Mar" with id "P1" of account "666" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        Given property "Hotel Sol" with id "P2" of account "666" exists
        Given property "P2" is located at "39.4800", "-0.3800"
        Given account trip calculator "TL1" allows destinations in properties "P1"
        When I go to "/es/carbon-footprint/trip/token-abc/destinations?query=Hotel"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "property:P1", "label": "Hotel Mar"}]
        """

    Scenario: A calculator for all properties lists every allowed property before the visitor types
        Given property "Hotel Mar" with id "P1" of account "666" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        Given property "Apartamentos Sol" with id "P2" of account "666" exists
        Given property "P2" is located at "39.4800", "-0.3800"
        Given account "Otra cadena" with id "555" exists
        Given property "Hotel Ajeno" with id "P3" of account "555" exists
        Given property "P3" is located at "39.4900", "-0.3900"
        Given account trip calculator "TL1" allows destinations in all properties
        When I go to "/es/carbon-footprint/trip/token-abc/destinations?query="
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "property:P2", "label": "Apartamentos Sol"}, {"value": "property:P1", "label": "Hotel Mar"}]
        """

    Scenario: A calculator limited to some properties lists only the selected ones before the visitor types
        Given property "Hotel Mar" with id "P1" of account "666" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        Given property "Apartamentos Sol" with id "P2" of account "666" exists
        Given property "P2" is located at "39.4800", "-0.3800"
        Given account trip calculator "TL1" allows destinations in properties "P1"
        When I go to "/es/carbon-footprint/trip/token-abc/destinations?query="
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "property:P1", "label": "Hotel Mar"}]
        """

    Scenario: A calculator for properties filters by name from the first letter
        Given property "Hotel Mar" with id "P1" of account "666" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        Given property "Apartamentos Sol" with id "P2" of account "666" exists
        Given property "P2" is located at "39.4800", "-0.3800"
        Given account trip calculator "TL1" allows destinations in all properties
        When I go to "/es/carbon-footprint/trip/token-abc/destinations?query=s"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "property:P2", "label": "Apartamentos Sol"}]
        """

    Scenario: A free-destination calculator does not suggest anything for an empty query
        When I go to "/es/carbon-footprint/trip/token-abc/destinations?query="
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        []
        """

    Scenario: The destination field preloads its options when the destinations are properties
        Given account trip calculator "TL1" allows destinations in all properties
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        Then the response status should be 200
        Then I see a text 'data-autocomplete-preload-value="true"'

    Scenario: The destination field does not preload options when the destination is free
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        Then the response status should be 200
        Then I see a text 'data-autocomplete-preload-value="false"'

    Scenario: The account calculator uses its own public routes and still answers through the property ones
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/trip/token-abc"
        Then I am redirected to route "public_carbon_footprint_trip_trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:valencia|cameFromAnotherCity:yes|originPlaceReference:sevilla|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:650|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then I see a text "Compensa tu huella"
        Then I see a text "Puedes contribuir a uno de los proyectos ambientales y sociales disponibles."
        Then I do not see a text "de este alojamiento"
        When I click on link "Compensar mi huella"
        Then the response status should be 200
        Then the current route should be "public_carbon_footprint_trip_contribution_choose"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the response status should be 200
        Then I see a text "¿Cuál es el destino de tu viaje?"

    Scenario: A calculator limited to overnight stays asks for the destination but not for the trip
        Given trip calculation link "TL1" has scope "overnight_stay"
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        Then the response status should be 200
        Then I see a text "¿Cuál es el destino de tu viaje?"
        Then I do not see a text "¿Tu viaje incluye desplazamiento?"
        Then I do not see a text "¿Desde dónde sales?"
        Then I see a text "Nº de noches"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:valencia|nights:2|people:2" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then I see a text "Destino: Valencia, España"
        Then trip calculation of link "TL1" should have destination "Valencia, España"
