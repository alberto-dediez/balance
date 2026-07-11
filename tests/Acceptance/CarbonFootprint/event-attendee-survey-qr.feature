Feature:
    I want to make sure that the attendee survey QR code and link are visible when editing an event

    Scenario: The attendee survey link and QR are shown on the event edit page
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/edit/333"
        Then the response status should be 200
        Then I see a text "Encuesta de asistentes"
        Then I see a text "Enlace de la encuesta de asistentes"
        Then I see a text "Copiar"
