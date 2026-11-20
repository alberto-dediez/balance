Feature:
    Ability for an attendee to leave their contact details when there is no credit to compensate their footprint

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"

    Scenario: Without projects an attendee leaves their contact details
        Then the response status should be 200
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then the response status should be 200
        Then the current route should be "public_carbon_footprint_attendee_survey_contribution_contact"
        Then I see a text "¡Gracias por tu compromiso!"
        Then I see a text "Equipo Supply Bioscore"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ESP" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_contribution_contact_thanks"
        Then the response status should be 200
        Then I see a text "¡Gracias por tu interés!"
        Then there should be 1 contact requests
        Then the only contact request should be "pending"
        Then the only contact request should have email "ana@example.com"
        Then user "payment" should have 1 internal notifications

    Scenario: Without an email the form is shown again and nothing is registered
        When I click on link "Compensar mi huella"
        When I submit a form with "name:Ana García|email:|country:ESP" and button "Enviar"
        Then the response status should be 200
        Then I see a text "¡Gracias por tu compromiso!"
        Then there should be 0 contact requests

    Scenario: A second submission updates the pending contact request without notifying again
        When I click on link "Compensar mi huella"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ESP" and button "Enviar"
        When I follow redirect
        When I click on link "Volver al resultado"
        When I click on link "Compensar mi huella"
        When I submit a form with "name:Ana García|email:ana.garcia@example.com|country:ESP" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_contribution_contact_thanks"
        Then I see a text "¡Gracias por tu interés!"
        Then there should be 1 contact requests
        Then the only contact request should have email "ana.garcia@example.com"
        Then user "payment" should have 1 internal notifications

    Scenario: A second submission does not modify a managed contact request
        When I click on link "Compensar mi huella"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ESP" and button "Enviar"
        When I follow redirect
        Given the only contact request is managed
        When I click on link "Volver al resultado"
        When I click on link "Compensar mi huella"
        When I submit a form with "name:Ana García|email:ana.garcia@example.com|country:ESP" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_contribution_contact_thanks"
        Then I see a text "¡Gracias por tu interés!"
        Then there should be 1 contact requests
        Then the only contact request should be "managed"
        Then the only contact request should have email "ana@example.com"
        Then user "payment" should have 1 internal notifications

    Scenario: With an adhered project without credit an attendee leaves their contact details
        When I click on link "Compensar mi huella"
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        When I click on link "Volver al resultado"
        Then I see a text "Compensa tu huella"
        When I click on link "Compensar mi huella"
        Then the current route should be "public_carbon_footprint_attendee_survey_contribution_contact"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ESP" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_contribution_contact_thanks"
        Then there should be 1 contact requests

    Scenario: Choosing a project once the credit is used up leads to the contact page
        When I click on link "Compensar mi huella"
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I click on link "Volver al resultado"
        Given contribution "C1" of account "666" for project "1111" with "10" tonnes is pending until "+12 hours"
        When I click on link "Compensar mi huella"
        Then the response status should be 302
        Then I am redirected to route "public_carbon_footprint_attendee_survey_contribution_contact"
        Then I see a text "¡Gracias por tu compromiso!"

    Scenario: Going to the contact page when there is credit leads to choosing a project
        When I click on link "Compensar mi huella"
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ESP" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_contribution_choose"
        Then I see a text "Elige un proyecto"
        Then there should be 0 contact requests
