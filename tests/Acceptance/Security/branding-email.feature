Feature:
    I want the certificate email of an account to carry that account's branding

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

    Scenario: The certificate email uses the account palette
        Given account "666" has branding with primary colour "#8A2432"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        When the gateway notifies that the payment of the only contribution succeeded
        When the queued messages are processed
        When I email the certificate of the only contribution with attendee survey token "token-abc"
        Then the email with subject "Tu certificado de compensación de carbono" contains "color: #8A2432"
        Then the email with subject "Tu certificado de compensación de carbono" contains "Poppins"

    Scenario: The certificate email of an account without branding keeps the platform palette
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"
        When I click on link "Compensar mi huella"
        When I click on link "Aportar"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Recalcular"
        When I submit a form with "mode:emissions|name:Ana|email:ana@example.com|country:ES" and button "Pagar"
        When the gateway notifies that the payment of the only contribution succeeded
        When the queued messages are processed
        When I email the certificate of the only contribution with attendee survey token "token-abc"
        Then the email with subject "Tu certificado de compensación de carbono" contains "color: #3E7490"
        Then the email with subject "Tu certificado de compensación de carbono" does not contain "#8A2432"
