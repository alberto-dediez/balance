Feature:
    Ability for an attendee to compensate the footprint of their attendance by contributing to a project

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"

    Scenario: An attendee offsets their emissions and the payment confirms the contribution
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
        Then I see a text "Aportación al proyecto"
        Then there should be 0 contributions
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should be "pending"
        When the gateway notifies that the payment of the only contribution succeeded
        Then the only contribution should be "paid"

    Scenario: An attendee reviews the details of a project before contributing
        When I click on link "Compensar mi huella"
        When I click on link "Ver detalles"
        Then the response status should be 200
        Then I see a text "Reforestación"
        Then I see a text "project-description"
        When I click on link "Aportar a este proyecto"
        Then the response status should be 200
        Then I see a text "Elige tu país para ver el desglose"

    Scenario: The details of a project that is not offered here are not shown
        Given project "Otro" with id "9999" and price per tonne "20" exists
        When I click on link "Compensar mi huella"
        When I click on link "Ver detalles"
        Then the response status should be 200
        When I go to the details of project "9999" in the current compensation flow
        Then the response status should be 404

    Scenario: An attendee makes a free contribution instead of offsetting their footprint
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Desglose"
        When I submit a form with "mode:free|amount:40|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        Then I am redirected to the payment gateway
        Then the only contribution should be "pending"
        Then account "666" should have "8" available tonnes in project "1111"

    Scenario: A free contribution above the credit left is refused before paying
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:400|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "no tiene crédito suficiente para el importe que has indicado"
        Then I do not see a text "Pagar"
        Then there should be 0 contributions

    Scenario: A free amount out of range is refused before quoting anything
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:1e9|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "El importe debe ser un número"
        Then there should be 0 contributions

    Scenario: A contribution without a country is not quoted nor paid
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com" and button "Recalcular"
        Then the response status should be 200
        Then I see a text "Elige tu país para ver el desglose"
        Then I do not see a text "Pagar"
        Then there should be 0 contributions

    Scenario: The breakdown is refreshed on its own without reloading the page
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:free|amount:40|country:ES" and button "Recalcular" as an ajax request
        Then the response status should be 200
        Then I see a text "Desglose"
        Then I see a text "Pagar"
        Then I do not see a text "Tu aportación"
        Then there should be 0 contributions

    Scenario: The attendee downloads the certificate of their contribution from the thank-you page
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        Then the only contribution should be "pending"
        When the gateway notifies that the payment of the only contribution succeeded
        When the queued messages are processed
        When I go to the thank-you page of the only contribution with attendee survey token "token-abc"
        Then the response status should be 200
        Then I see a text "¡Gracias por tu aportación!"
        Then I see a text "Descargar certificado"
        When I download the certificate of the only contribution with attendee survey token "token-abc"
        Then the response status should be 200

    Scenario: The certificate cannot be downloaded with an invalid survey token
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        When the gateway notifies that the payment of the only contribution succeeded
        When the queued messages are processed
        When I download the certificate of the only contribution with attendee survey token "not-a-token"
        Then the response status should be 404

    Scenario: A closed survey does not stop an attendee that already answered from compensating
        Given attendee survey "AS1" is closed with 100 real attendees and confidence level 95
        When I click on link "Compensar mi huella"
        Then the response status should be 200
        Then I see a text "Elige un proyecto"

    Scenario: Without projects behind it the result screen does not offer compensating
        Given account "Otra" with id "999" exists
        Given attendee survey with id "AS2" and token "token-xyz" for event "333" of account "999" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-xyz/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-xyz/city"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"
        Then the response status should be 200
        Then I do not see a text "Compensa tu huella"
