Feature:
    I want to make sure that the owner of an account can configure and consult its trip calculator

    Background:
        Given account "Viajes Sostenibles" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists

    Scenario: The owner opens the trip calculator of their account and gets its public link
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator"
        Then the response status should be 200
        Then I see a text "Calculadora de huella de viajes"
        Then I see a text "Enlace de la calculadora de viajes"
        Then I see a text "/carbon-footprint/trip/"
        Then I see a text "Destino del viaje"

    Scenario: The owner limits the destinations to some countries
        Given country "España" with iso "ESP" and id "C-ES" exists
        Given country "Sin ISO" with id "C-XX" exists
        Given country "Inventado" with iso "XYZ" and id "C-XY" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator"
        Then I see an option "C-ES" in form field "[countryIds]"
        Then I do not see an option "C-XX" in form field "[countryIds]"
        Then I do not see an option "C-XY" in form field "[countryIds]"
        When I submit a form with "mode:countries|countryIds:[C-ES]" and button "Guardar"
        Then I am redirected to route "carbon_footprint_trip_calculator"
        Then the response status should be 200
        Then I see a text "Calculadora actualizada con éxito"
        Then I see an option "C-ES" in form field "[countryIds]" with attribute "selected" "selected"

    Scenario: The owner limits the destinations to some of their properties
        Given property "Hotel Mar" with id "P1" of account "666" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator"
        When I submit a form with "mode:selected_properties|propertyIds:[P1]" and button "Guardar"
        Then I am redirected to route "carbon_footprint_trip_calculator"
        Then I see a text "Calculadora actualizada con éxito"
        Then I see an option "P1" in form field "[propertyIds]" with attribute "selected" "selected"

    Scenario: A consultant tells apart the properties with the same name of its managed accounts
        Given account "Greenhost" with id "670" is a consultant
        Given user "consultant@dediez.software" with role "ROLE_OWNER" of account "670" exists
        Given account "Hoteles Norte" with id "671" exists
        Given account "671" has consultant "670"
        Given account "Hoteles Sur" with id "672" exists
        Given account "672" has consultant "670"
        Given property "Hotel Centro" with id "P1" of account "671" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        Given property "Hotel Centro" with id "P2" of account "672" exists
        Given property "P2" is located at "39.4700", "-0.3764"
        When I am logged as "consultant@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator"
        Then I see an option "P1" in form field "[propertyIds]"
        Then I see an option "P2" in form field "[propertyIds]"
        Then I see a text "Hotel Centro (Hoteles Norte)"
        Then I see a text "Hotel Centro (Hoteles Sur)"

    Scenario: The owner tells apart the properties with the same name of their own account
        Given property "Hotel Centro" with id "P1" of account "666" exists
        Given property "P1" is located at "39.4699", "-0.3763"
        Given property "Hotel Centro" with id "P2" of account "666" exists
        Given property "P2" is located at "39.4700", "-0.3764"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator"
        Then I see an option "P1" in form field "[propertyIds]"
        Then I see an option "P2" in form field "[propertyIds]"
        Then I see a text "Hotel Centro (Viajes Sostenibles) (2)"

    Scenario: The owner cannot save a list of countries without any country
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator"
        When I submit a form with "mode:countries" and button "Guardar"
        Then the response status should be 200
        Then I see a text "Debes elegir entre 1 y 15 países."

    Scenario: The owner of another account gets their own calculator and a plain user cannot see any
        Given account trip calculator with id "TL1" and token "token-abc" of account "666" exists
        Given account "Otra agencia" with id "667" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "667" exists
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator"
        Then the response status should be 200
        Then I do not see a text "token-abc"
        When I am logged as "user@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator/responses"
        Then the response status should be 403
        When I go to "/es/carbon-footprint/trip-calculator"
        Then the response status should be 403

    Scenario: The owner sees the top destinations and the map of the trips
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given account trip calculator with id "TL1" and token "token-abc" of account "666" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        Given place "valencia" named "Valencia, España" at "39.4699", "-0.3763" exists
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:valencia|cameFromAnotherCity:yes|originPlaceReference:sevilla|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:650|roundTrip:no|trips:1|overnight:no" and button "Calcular mi huella"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/trip-calculator/responses"
        Then the response status should be 200
        Then I see a text "Top de destinos"
        Then I see a text "Mapa de viajes"
        Then I see a text "Cada flecha une un origen con su destino"
        Then I do not see a text "Todavía no hay destinos registrados."
        Then I do not see a text "Todavía no hay viajes geolocalizados."
        Then I see a text "[&quot;Valencia, Espa\u00f1a&quot;]"
        Then I see a text "&quot;destLat&quot;:39.47"
        Then I see a text "&quot;lat&quot;:37.39"

    Scenario: The owner exports the calculations of the trip calculator of their account
        Given account trip calculator with id "TL1" and token "token-abc" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/account_trip_calculations/export?accountId=667"
        Then the response status should be 200
        Then I do not see a form field "[filter][accountId]"
        When I submit a form with "" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: A plain user cannot export the calculations of the trip calculator
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/account_trip_calculations/export"
        Then the response status should be 403
