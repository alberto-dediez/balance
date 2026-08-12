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

    Scenario: An owner of another account cannot see the responses page of an event
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "667" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "667" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/events/333/attendee-survey/responses"
        Then the response status should be 403
