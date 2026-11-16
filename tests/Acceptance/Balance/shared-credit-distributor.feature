Feature:
    Ability for the guests of a distributed account to compensate their footprint with the credit of its distributor

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given user "owner@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given account "Hotel A" with id "1111" exists
        Given user "owner@hotel.test" with role "ROLE_OWNER" of account "1111" exists
        Given property "Hotel Playa" with id "11" of account "1111" exists
        Given trip calculation link with id "TL1" and token "token-hotel" for property "11" of account "1111" exists
        Given project "Reforestación" with id "9999" and price per tonne "20" exists
        Given project "9999" is adhered to account "555"

    Scenario: A guest of a distributed property compensates with the credit of the distributor
        Given distributor "555" is assigned to account "1111"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I go to "/es/carbon-footprint/property-trip/token-hotel/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then I see a text "Reforestación"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should belong to account "1111" backed by the credit of account "555"
        Then account "555" should have "8.347" available tonnes in project "9999"
        Then account "1111" should have "0" available tonnes in project "9999"
        When the gateway notifies that the payment of the only contribution succeeded
        Then the only contribution should be "paid"
        Then the only contribution should consume "1.653" tonnes of credit purchase "2222"
        Then account "555" should have "8.347" available tonnes in project "9999"

    Scenario: A free contribution above the own credit but within the shared one is backed by the distributor
        Given distributor "555" is assigned to account "1111"
        Given project "9999" is adhered to account "1111"
        Given credit purchase "3333" for account "1111" and project "9999" requesting "1" tonnes exists
        Given credit purchase "3333" is validated with "1" tonnes and reference "REF-OWN"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I go to "/es/carbon-footprint/property-trip/token-hotel/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Pagar 40,00 €"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should belong to account "1111" backed by the credit of account "555"
        Then account "1111" should have "1" available tonnes in project "9999"
        Then account "555" should have "8.347" available tonnes in project "9999"

    Scenario: A hotel with enough credit of its own uses it and leaves the distributor credit untouched
        Given distributor "555" is assigned to account "1111"
        Given project "9999" is adhered to account "1111"
        Given credit purchase "3333" for account "1111" and project "9999" requesting "5" tonnes exists
        Given credit purchase "3333" is validated with "5" tonnes and reference "REF-OWN"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I go to "/es/carbon-footprint/property-trip/token-hotel/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should belong to account "1111" backed by the credit of account "1111"
        Then account "1111" should have "3.347" available tonnes in project "9999"
        Then account "555" should have "10" available tonnes in project "9999"

    Scenario: The lots the distributor paid itself are not shared
        Given distributor "555" is assigned to account "1111"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_client"
        When I go to "/es/carbon-footprint/property-trip/token-hotel/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I do not see a text "Compensa tu huella"

    Scenario: A property assignment does not cover the trip calculator of the account
        Given distributor "555" is assigned to property "11"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given account trip calculator with id "TL2" and token "token-account" of account "1111" exists
        Given place "valencia" named "Valencia, España" at "39.4699", "-0.3763" in country "ES" and areas "Valencia, Comunitat Valenciana, España" exists
        When I go to "/es/carbon-footprint/property-trip/token-hotel/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I see a text "Compensa tu huella"
        When I go to "/es/carbon-footprint/trip/token-account/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:valencia|cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then I do not see a text "Compensa tu huella"

    Scenario: A client of the trip calculator of a distributed account compensates with the credit of the distributor
        Given distributor "555" is assigned to account "1111"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given account trip calculator with id "TL2" and token "token-account" of account "1111" exists
        Given place "valencia" named "Valencia, España" at "39.4699", "-0.3763" in country "ES" and areas "Valencia, Comunitat Valenciana, España" exists
        When I go to "/es/carbon-footprint/trip/token-account/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "destinationPlaceReference:valencia|cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_trip_result"
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then I see a text "Reforestación"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should belong to account "1111" backed by the credit of account "555"

    Scenario: Without compensation in the distributed account the distributor credit is not offered
        Given distributor "555" is assigned to account "1111"
        Given account "1111" has feature "compensation" disabled
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I go to "/es/carbon-footprint/property-trip/token-hotel/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I do not see a text "Compensa tu huella"

    Scenario: Without compensation in the distributor its credit is not offered
        Given distributor "555" is assigned to account "1111"
        Given account "555" has feature "compensation" disabled
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I go to "/es/carbon-footprint/property-trip/token-hotel/trip"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "cameFromAnotherCity:no|overnight:no" and button "Calcular mi huella"
        Then I am redirected to route "public_carbon_footprint_property_trip_result"
        Then I do not see a text "Compensa tu huella"

    Scenario: An attendee of an event of a distributed account compensates with the credit of the distributor
        Given distributor "555" is assigned to account "1111"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given event "Evento A" with id "333" of account "1111" exists
        Given attendee survey with id "AS1" and token "token-survey" for event "333" of account "1111" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-survey/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-survey/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then I see a text "Reforestación"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should belong to account "1111" backed by the credit of account "555"

    Scenario: The owner of a distributed account does not see the distributor credit when compensating their event
        Given distributor "555" is assigned to account "1111"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given event "Congreso" with id "888" of account "1111" exists
        Given event carbon footprint with id "999" for event "888" of account "1111" completed exists
        When I am logged as "owner@hotel.test"
        When I go to "/es/compensate/event_carbon_footprint/888"
        Then the response status should be 200
        Then I do not see a text "Reforestación"
        Then I do not see a text "Compensar con tus créditos"

    Scenario: The owner of a distributed account does not see the distributor lots in their credits
        Given distributor "555" is assigned to account "1111"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "1111" backed by the credit of account "555" for project "9999" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Then contribution "C1" should consume "2" tonnes of credit purchase "2222"
        When I am logged as "owner@hotel.test"
        When I go to "/es/credits"
        Then the response status should be 200
        Then I do not see a text "REF-1"
        When I am logged as "owner@greenhost.test"
        When I go to "/es/credits"
        Then the response status should be 200
        Then I see a text "REF-1"
        Then account "555" should have "8" available tonnes in project "9999"

    Scenario: The distributor does not get a link to the contributions of its distributed accounts in the detail of a lot
        Given distributor "555" is assigned to account "1111"
        Given credit purchase "2222" for account "555" and project "9999" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "1111" backed by the credit of account "555" for project "9999" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Given contribution "C2" of account "555" for project "9999" with "3" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C2" succeeded
        Then contribution "C1" should consume "2" tonnes of credit purchase "2222"
        Then contribution "C2" should consume "3" tonnes of credit purchase "2222"
        When I am logged as "owner@greenhost.test"
        When I go to "/es/credits/purchases/2222"
        Then the response status should be 200
        Then I see a text "Compensaciones"
        Then I see a link to "/es/credits/contributions/C2"
        Then I do not see a link to "/es/credits/contributions/C1"
