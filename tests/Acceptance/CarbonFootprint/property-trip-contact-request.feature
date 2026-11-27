Feature:
    Ability for a visitor of a trip calculator to leave their contact details when there is no credit to compensate their footprint

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists

    Scenario: Without projects a guest of a property leaves their contact details
        Given property "Hotel A" with id "777" of account "666" exists
        Given property "777" has type "Other"
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then the current route should be "public_carbon_footprint_property_trip_contribution_contact"
        Then I see a text "¡Gracias por tu compromiso!"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ESP" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_property_trip_contribution_contact_thanks"
        Then I see a text "¡Gracias por tu interés!"
        Then there should be 1 contact requests
        Then the only contact request should be "pending"
        Then user "payment" should have 1 internal notifications

    Scenario: Without projects a visitor of an account trip calculator leaves their contact details
        Given account trip calculator with id "TL1" and token "token-abc" of account "666" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" in country "ES" and areas "Sevilla, Andalucía, España" exists
        Given place "paris" named "París, Francia" at "48.8566", "2.3522" in country "FR" and areas "París, Île-de-France, Francia" exists
        When I go to "/es/carbon-footprint/trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:paris|cameFromAnotherCity:yes|originPlaceReference:sevilla|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:1800|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then the current route should be "public_carbon_footprint_trip_contribution_contact"
        Then I see a text "¡Gracias por tu compromiso!"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ESP" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_trip_contribution_contact_thanks"
        Then I see a text "¡Gracias por tu interés!"
        Then there should be 1 contact requests
        Then user "payment" should have 1 internal notifications

    Scenario: Without compensation the result screen does not offer contacting
        Given account "666" has feature "compensation" disabled
        Given property "Hotel A" with id "777" of account "666" exists
        Given property "777" has type "Other"
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then the response status should be 200
        Then I do not see a text "Compensa tu huella"
        Then I do not see a link with name "Compensar mi huella"
