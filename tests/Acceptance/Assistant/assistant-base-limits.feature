Feature:
    The daily cost limit of the AI assistant in Hydra

    Scenario: Once the limit is reached no message is accepted
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "admin@dediez.software" has spent "5.000000" USD on the assistant today
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        Then I see a text "Hoy: 5,00 / 5,00 USD"
        When I submit a form with "text:Hola" and button "Enviar"
        Then the response status should be 422
        Then I see a text "Has alcanzado el límite diario del asistente"

    Scenario: Once the limit is reached no plan is executed
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the assistant will call the tool "propose_plan" with:
            """
            {"summary": "Crear la cuenta Acme Labs", "steps": [{"tool": "register_account", "arguments": [{"name": "name", "value": "Acme Labs", "fromStep": null}, {"name": "active", "value": "true", "fromStep": null}]}]}
            """
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:Crea la cuenta Acme Labs" and button "Enviar"
        When the assistant processes its pending work
        Given user "admin@dediez.software" has spent "5.000000" USD on the assistant today
        When I execute the last plan
        Then the response status should be 422
        Then I see a text "Has alcanzado el límite diario del asistente"
        Then the plan of the last conversation has status "proposed"
        When I go to "/es/admin/accounts"
        Then I do not see a text "Acme Labs"
