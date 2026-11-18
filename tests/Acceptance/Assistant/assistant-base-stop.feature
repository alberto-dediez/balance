Feature:
    The admin stops the pending work of the AI assistant in Hydra

    Background:
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"

    Scenario: Stopping a pending answer leaves it stopped and allows sending another message
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué cuentas hay?" and button "Enviar"
        Then the response status should be 200
        When I stop the assistant conversation
        Then the response status should be 204
        Then the last message of the last conversation is failed with "Respuesta detenida"
        When the assistant processes its pending work
        Then the last message of the last conversation is failed with "Respuesta detenida"
        When I open the thread of the last conversation
        Then I see a text "Respuesta detenida"
        Given the assistant will answer with text "Esta sí llega"
        When I open the panel of the last conversation
        When I submit a form with "text:Prueba de nuevo" and button "Enviar"
        Then the response status should be 200
        When the assistant processes its pending work
        When I open the thread of the last conversation
        Then I see a text "Esta sí llega"

    Scenario: Stopping a plan execution interrupts the plan
        Given a proposed plan with id "0190f3a4-1b2c-7d3e-8f40-0000000000b1" in conversation "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "admin@dediez.software" exists
        When I execute the last plan
        Then the response status should be 204
        When I stop the assistant conversation
        Then the response status should be 204
        Then the plan of the last conversation has status "failed"
        Then the last message of the last conversation is failed with "Ejecución detenida. Revisa los pasos realizados."
        When the assistant processes its pending work
        Then the plan of the last conversation has status "failed"
