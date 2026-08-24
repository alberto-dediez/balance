Feature:
    I want to make sure that the attendee survey QR code and link are visible in the calculator page of an event

    Scenario: The attendee survey link and QR are shown in the calculator page
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/attendee-survey"
        Then the response status should be 200
        Then I see a text "Encuesta de asistentes"
        Then I see a text "Enlace de la encuesta de asistentes"
        Then I see a text "Copiar"

    Scenario: An owner of another account cannot see the calculator page of an event
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "667" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "667" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/events/333/attendee-survey"
        Then the response status should be 403

    Scenario: The responses page of an event without a carbon footprint shows no figures
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/attendee-survey/responses"
        Then the response status should be 200
        Then I see a text "Este evento todavía no tiene un cálculo de huella de carbono"

    Scenario: The responses page shows the dashboard of the answers of the attendees
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint for event "333" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|originPlaceReference:sevilla|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/attendee-survey/responses"
        Then the response status should be 200
        Then I see a text "Respuestas recibidas"
        Then I see a text "Emisiones declaradas"
        Then I see a text "Distancia por medio de transporte"
        Then I see a text "Perfil de los asistentes"
        Then I see a text "Coche"
        Then I see a text "Sevilla"

    Scenario: An owner of another account cannot see the responses page of an event
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "667" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "667" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/events/333/attendee-survey/responses"
        Then the response status should be 403
