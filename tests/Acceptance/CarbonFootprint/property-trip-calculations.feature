Feature:
    I want to make sure that the owner of a property can consult the aggregated footprint of their guests

    Scenario: The calculator link and QR are shown in the carbon footprint screen of a property
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777"
        Then the response status should be 200
        Then I see a text "Calculadora de huella de huéspedes"
        Then I see a text "Enlace de la calculadora de huéspedes"
        Then I see a text "Copiar"

    Scenario: The owner sees the aggregated results of their guests
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given the places provider is disabled
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:120|roundTrip:no|trips:1|overnight:no" and button "Calcular mi huella"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777/responses"
        Then the response status should be 200
        Then I see a text "Respuestas"
        Then I see a text "Desglose por medio de transporte"
        Then I see a text "Coche"

    Scenario: The results screen is empty for a property without answers
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777/responses"
        Then the response status should be 200
        Then I see a text "Todavía no hay respuestas en este periodo"

    Scenario: An owner of another account cannot see the results
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "667" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "667" exists
        Given property "Hotel A" with id "777" of account "666" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777/responses"
        Then the response status should be 403

    Scenario: The owner limits the calculator to the overnight stays
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777"
        Then the response status should be 200
        Then I see a text "Qué recoge la calculadora"
        When I submit a form with "scope:overnight_stay" and button "Aplicar"
        Then I am redirected to route "carbon_footprint_property_trip_calculations"
        Then the response status should be 200
        Then I see a text "Comparte este QR o su enlace con los huéspedes para recoger sus pernoctaciones."

    Scenario: The answers screen keeps showing the overnight stays already collected
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given the places provider is disabled
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:120|roundTrip:no|trips:1|overnight:yes|nights:3" and button "Calcular mi huella"
        Given trip calculation link "TL1" has scope "transport"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777/responses"
        Then the response status should be 200
        Then I see a text "Pernoctaciones"
