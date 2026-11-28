Feature:
    I want to make sure that a visitor who calculates their footprint can enter the contest of the calculator

    Background:
        Given account "MyCompany" with id "666" exists
        Given account "666" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given country "España" with iso "ESP" and id "C-ES" exists
        Given country "Portugal" with iso "PRT" and id "C-PT" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given overnight stay "Hotel" with id "O1" and emission factor for the current year exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists

    Scenario: The attendee survey announces the contest and the visitor enters it from the result
        Given contest "Concurso de verano" with id "C1" of account "666" for "event" "333" exists
        Given contest "C1" is published
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        Then I see a text "Concurso de verano"
        Then I see a text "Calcula tu huella para participar"
        Then I see a link to "/es/carbon-footprint/attendee-survey/token-abc/contest/terms"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"
        Then I see a text "Premio del concurso Concurso de verano"
        When I click on link "Participar en el concurso"
        Then the response status should be 200
        Then I see a text "Organizador Concurso de verano"
        When I tick the checkbox "register_contest_entry[consent]"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ES" and button "Participar en el concurso"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_contest_thanks"
        Then I see a text "Ya estás apuntado."
        Then I see a text "Si resultas ganador, Organizador Concurso de verano se pondrá en contacto contigo."
        When I click on link "Volver al resultado"
        Then I see a text "Ya estás apuntado al concurso."
        Then I do not see a link with name "Participar en el concurso"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Ana García"
        Then I see a text "Evento A"

    Scenario: Without ticking the consent the visitor is not entered
        Given contest "Concurso de verano" with id "C1" of account "666" for "event" "333" exists
        Given contest "C1" is published
        Given attendee survey response with id "R1" for attendee survey "AS1" using mobility "M1" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/result/R1/contest"
        Then the response status should be 200
        When I submit a form with "name:Ana García|email:ana@example.com|country:ES" and button "Participar en el concurso"
        Then the response status should be 200
        Then I do not see a text "Ya estás apuntado."
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Todavía no hay nadie apuntado a este concurso."

    Scenario: A visitor of a country that is not admitted cannot enter
        Given contest "Concurso de verano" with id "C1" of account "666" for "event" "333" exists
        Given contest "C1" only admits countries "PT"
        Given contest "C1" is published
        Given attendee survey response with id "R1" for attendee survey "AS1" using mobility "M1" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/result/R1/contest"
        Then I see an option "PT" in form field "[country]"
        Then I do not see an option "ES" in form field "[country]"

    Scenario: The same email cannot enter twice
        Given contest "Concurso de verano" with id "C1" of account "666" for "event" "333" exists
        Given contest "C1" is published
        Given contest "C1" has a confirmed entry with id "N1" named "Ana García" and email "ana@example.com"
        Given attendee survey response with id "R1" for attendee survey "AS1" using mobility "M1" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/result/R1/contest"
        When I tick the checkbox "register_contest_entry[consent]"
        When I submit a form with "name:Ana García|email:ana@example.com|country:ES" and button "Participar en el concurso"
        Then the response status should be 200
        Then I see a text "Ya hay una inscripción con este email."

    Scenario: Out of its period the contest is not offered
        Given contest "Concurso de verano" with id "C1" of account "666" for "event" "333" exists
        Given contest "C1" runs from "-10 days" to "-1 day"
        Given contest "C1" is published
        Given attendee survey response with id "R1" for attendee survey "AS1" using mobility "M1" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/result/R1"
        Then the response status should be 200
        Then I do not see a text "Concurso de verano"
        Then I do not see a link with name "Participar en el concurso"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/result/R1/contest"
        Then I am redirected to route "public_carbon_footprint_attendee_survey_result"

    Scenario: An account without the contests feature does not offer the contest
        Given account "Otra" with id "777" exists
        Given event "Evento B" with id "444" of account "777" exists
        Given attendee survey with id "AS2" and token "token-def" for event "444" of account "777" exists
        Given contest "Concurso de verano" with id "C1" of account "777" for "event" "444" exists
        Given contest "C1" is published
        Given attendee survey response with id "R2" for attendee survey "AS2" using mobility "M1" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-def/result/R2"
        Then the response status should be 200
        Then I do not see a text "Concurso de verano"

    Scenario: A contest in two calculators receives entries from both and removing one of them hides it there
        Given property "Hotel A" with id "P1" of account "666" exists
        Given property "P1" is located at "40.4680", "-3.6160"
        Given trip calculation link with id "TL1" and token "token-hotel" for property "P1" of account "666" exists
        Given trip calculation "TC1" of link "TL1" exists
        Given contest "Concurso de verano" with id "C1" of account "666" for "event" "333" exists
        Given contest "C1" is also offered in "property" "P1"
        Given contest "C1" is published
        When I go to "/es/carbon-footprint/property-trip/token-hotel/result/TC1"
        Then I see a link with name "Participar en el concurso"
        When I click on link "Participar en el concurso"
        When I tick the checkbox "register_contest_entry[consent]"
        When I submit a form with "name:Bea López|email:bea@example.com|country:ES" and button "Participar en el concurso"
        Then I am redirected to route "public_carbon_footprint_property_trip_contest_thanks"
        Given contest "C1" is no longer offered in "property" "P1"
        When I go to "/es/carbon-footprint/property-trip/token-hotel/result/TC1"
        Then I do not see a link with name "Participar en el concurso"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Bea López"
        Then I see a text "Hotel A"
        Then I do not see a text "Calculadora retirada del concurso"

    Scenario: The account trip calculator offers the contest
        Given account trip calculator with id "TL2" and token "token-trip" of account "666" exists
        Given trip calculation "TC2" of link "TL2" exists
        Given contest "Concurso de verano" with id "C1" of account "666" for "trip_link" "TL2" exists
        Given contest "C1" is published
        When I go to "/es/carbon-footprint/trip/token-trip/result/TC2"
        Then I see a link with name "Participar en el concurso"
        When I go to "/es/carbon-footprint/trip/token-trip/contest/terms"
        Then the response status should be 200
        Then I see a text "Bases del concurso Concurso de verano"
        Then I see a text "no organiza ni patrocina este concurso."
