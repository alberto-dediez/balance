Feature:
    Ability for a guest to compensate the footprint of their trip by contributing to a project

    Background:
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists

    Scenario: A guest offsets their emissions and the payment confirms the contribution
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then the response status should be 200
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then the response status should be 200
        Then I see a text "Elige un proyecto"
        Then I see a text "Reforestación"
        When I click on link "Aportar"
        Then the response status should be 200
        Then I see a text "Elige tu país para ver el desglose"
        Then I do not see a text "Margen de gestión"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Desglose"
        Then I see a text "Margen de gestión"
        Then I see a text "Aportación al proyecto"
        Then there should be 0 contributions
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should be "pending"

    Scenario: A guest reviews the details of a project before contributing
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Ver detalles"
        Then the response status should be 200
        Then I see a text "Reforestación"
        Then I see a text "project-description"
        When I click on link "Aportar a este proyecto"
        Then the response status should be 200
        Then I see a text "Elige tu país para ver el desglose"

    Scenario: The details of a project that is not offered here are not shown
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given project "Otro" with id "9999" and price per tonne "20" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Ver detalles"
        Then the response status should be 200
        When I go to the details of project "9999" in the current compensation flow
        Then the response status should be 404

    Scenario: A guest makes a free contribution and the payment confirms it
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        Then the response status should be 200
        Then I see a text "Tu visita no genera emisiones que compensar"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Desglose"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should be "pending"
        Then account "666" should have "8" available tonnes in project "1111"
        When the gateway notifies that the payment of the only contribution succeeded
        Then the only contribution should be "paid"
        Then account "666" should have "8" available tonnes in project "1111"

    Scenario: A free contribution above the credit left is refused before paying
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:400|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "no tiene crédito suficiente para el importe que has indicado"
        Then I do not see a text "Pagar"
        Then there should be 0 contributions

    Scenario: The breakdown is refreshed on its own without reloading the page
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|country:ES" and button "Recalcular" as an ajax request
        Then the response status should be 200
        Then I see a text "Desglose"
        Then I see a text "Pagar"
        Then I do not see a text "Tu aportación"
        Then there should be 0 contributions

    Scenario: A contribution without a country is not quoted nor paid
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Elige tu país para ver el desglose"
        Then I do not see a text "Margen de gestión"
        Then I do not see a text "Pagar"
        Then there should be 0 contributions

    Scenario: A free amount out of range is refused before quoting anything
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:1e9|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "El importe debe ser un número"
        Then I do not see a text "Desglose"
        Then there should be 0 contributions

    Scenario: An offsetting project without enough credit is not offered
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then the response status should be 200
        Then I do not see a text "Compensa tu huella"

    Scenario: A project without a price per tonne is not offered
        Given project "Reforestación" with id "1111" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I do not see a text "Compensa tu huella"

    Scenario: A social project is offered without any credit behind it
        Given social project "Escuela rural" with id "3333" and price per tonne "20" exists
        Given project "3333" is adhered to account "666"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then I see a text "Escuela rural"

    Scenario: The gateway confirms a pending contribution through the webhook
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        Then account "666" should have "8" available tonnes in project "1111"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Then the response status should be 200
        Then contribution "C1" should be "paid"
        Then account "666" should have "8" available tonnes in project "1111"

    Scenario: A failed payment releases the reserved credit
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" failed
        Then the response status should be 200
        Then contribution "C1" should be "failed"
        Then account "666" should have "10" available tonnes in project "1111"

    Scenario: An expired checkout session releases the reserved credit
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" expired
        Then the response status should be 200
        Then contribution "C1" should be "expired"
        Then account "666" should have "10" available tonnes in project "1111"

    Scenario: A payment landing on an already expired contribution is reported to the administrators
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "-1 hour"
        When the pending contributions are expired
        Then contribution "C1" should be "expired"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Then the response status should be 200
        Then contribution "C1" should be "expired"
        When I am logged as "admin@dediez.software"
        When I go to "/es/notifications"
        Then the response status should be 200
        Then I see a text "Pago recibido de una aportación ya cerrada"

    Scenario: An unsigned webhook is rejected
        When I make a POST request to "/webhooks/stripe"
        Then the response status should be 400

    Scenario: Expiring the reservations releases the credit of unpaid contributions
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "-1 hour"
        When the pending contributions are expired
        Then contribution "C1" should be "expired"
        Then account "666" should have "10" available tonnes in project "1111"
