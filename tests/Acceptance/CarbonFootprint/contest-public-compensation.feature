Feature:
    I want to make sure that a visitor who offsets their footprint can enter the contest of the calculator

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given account "666" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contest "Concurso solidario" with id "C1" of account "666" for "event" "333" with participation "compensation" and award "draw" exists
        Given contest "C1" is published
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"

    Scenario: The visitor enters the contest when the payment of the offset is confirmed
        Then I see a text "Concurso solidario"
        Then I see a text "Compensa tu huella para participar"
        Then I do not see a link with name "Participar en el concurso"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        Then I see a text "Solo quedarás apuntado si finalizas la compensación."
        When I tick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Pendiente de pago"
        When the gateway notifies that the payment of the only contribution succeeded
        Then the only contribution should be "paid"
        When I go to the payment return of the latest contribution of attendee survey "token-abc"
        Then the response status should be 200
        Then I see a text "¡Gracias por tu aportación!"
        Then I see a text "Ya estás apuntado."
        Then I see a text "Si resultas ganador, Organizador Concurso solidario se pondrá en contacto contigo."
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Confirmado"
        Then I see a text "ana@example.com"

    Scenario: Without ticking the box the visitor is not entered
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        When the gateway notifies that the payment of the only contribution succeeded
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Todavía no hay nadie apuntado a este concurso."

    Scenario: An expired payment removes the pending entry
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I tick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        When the gateway notifies that the payment of the latest contribution expired
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Todavía no hay nadie apuntado a este concurso."

    Scenario: A failed payment removes the pending entry
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I tick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        When the gateway notifies that the payment of the latest contribution failed
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Todavía no hay nadie apuntado a este concurso."

    Scenario: After cancelling the visitor can retry and is entered only once
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        When I tick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        When I go back to the checkout of the latest contribution of attendee survey "token-abc"
        When I submit a form with "mode:emissions|name:Ana López|email:ana.lopez@example.com|country:ESP" and button "Recalcular"
        Then I see a text "Solo quedarás apuntado si finalizas la compensación."
        Then I do not see a text "Ya estás apuntado al concurso."
        When I tick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Ana López|email:ana.lopez@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway
        Then there should be 2 contributions
        When the gateway notifies that the payment of the latest contribution succeeded
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a table with 2 rows
        Then I see a text "ana.lopez@example.com"
        Then I see a text "Confirmado"

    Scenario: A visitor of a country that is not admitted cannot pay with the box ticked
        Given contest "C1" is closed
        Given contest "Concurso portugués" with id "C2" of account "666" for "event" "333" with participation "compensation" and award "draw" exists
        Given contest "C2" only admits countries "PT"
        Given contest "C2" is published
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        Then I see a text "Solo pueden participar residentes en"
        When I tick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then the response status should be 200
        Then I see a text "Tu país no puede participar en este concurso. Desmarca la casilla para continuar."
        Then there should be 0 contributions
        When I untick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Pagar"
        Then I am redirected to the payment gateway

    Scenario: When the prizes of the first entrants are awarded the checkout warns about it
        Given contest "C1" is closed
        Given contest "Concurso de los primeros" with id "C2" of account "666" for "event" "333" with participation "compensation" and award "first_entrants" exists
        Given contest "C2" has 2 winners
        Given contest "C2" is published
        Given contest "C2" has 2 confirmed entries
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ESP" and button "Recalcular"
        Then I see a text "Los 2 premios de este concurso ya se han adjudicado. Puedes apuntarte, pero sin premio."
        When I tick the checkbox "contribution[contest]"
        When I submit a form with "mode:emissions|name:Carla|email:carla@example.com|country:ESP" and button "Pagar"
        When the gateway notifies that the payment of the latest contribution succeeded
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C2/entries"
        When I filter by "winner" with "1"
        Then I see a table with 3 rows
        Then I do not see a text "carla@example.com"
