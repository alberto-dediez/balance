Feature:
    The AI assistant proposes plans over the accounts and users of Hydra that only run with the «Ejecutar» button

    Background:
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the assistant will call the tool "propose_plan" with:
            """
            {
                "summary": "Crear la cuenta «Acme Labs» y el usuario «ana@acme.test»",
                "steps": [
                    {"tool": "register_account", "arguments": [
                        {"name": "name", "value": "Acme Labs", "fromStep": null},
                        {"name": "active", "value": "true", "fromStep": null}
                    ]},
                    {"tool": "register_user", "arguments": [
                        {"name": "username", "value": "ana@acme.test", "fromStep": null},
                        {"name": "accountId", "value": "", "fromStep": 1}
                    ]}
                ]
            }
            """
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:Crea la cuenta Acme Labs y un usuario para ella" and button "Enviar"
        When the assistant processes its pending work

    Scenario: An action request proposes a two-step plan and nothing is created yet
        Then the plan of the last conversation has status "proposed"
        When I open the thread of the last conversation
        Then I see a text "Plan propuesto"
        Then I see a text "Registrar cuenta"
        Then I see a text "Registrar usuario"
        Then I see a text "Resultado del paso 1"
        Then I see a button with name "Ejecutar"
        When I go to "/es/admin/accounts"
        Then I do not see a text "Acme Labs"
        When I go to "/es/admin/users"
        Then I do not see a text "ana@acme.test"

    Scenario: Writing «yes» in the chat does not execute the plan and discards it
        Given the assistant will answer with text "Pulsa el botón «Ejecutar» para ejecutar el plan."
        When I open the panel of the last conversation
        When I submit a form with "text:sí, ejecútalo" and button "Enviar"
        When the assistant processes its pending work
        Then the plan of the last conversation has status "discarded"
        When I go to "/es/admin/accounts"
        Then I do not see a text "Acme Labs"

    Scenario: «Ejecutar» creates the account and its user chained and records them as done by the assistant
        Given the assistant will call the tool "register_account" with:
            """
            {"name": "Acme Labs", "active": true}
            """
        Given the assistant will call the tool "register_user" with the references of the previous step and:
            """
            {"username": "ana@acme.test", "accountId": "@accountId"}
            """
        Given the assistant will answer with text "He creado la cuenta «Acme Labs» y su usuario."
        When I execute the last plan
        Then the response status should be 204
        When the assistant processes its pending work
        Then the plan of the last conversation has status "executed"
        Then I received an email with subject "Bienvenido ana@acme.test" to "ana@acme.test"
        When I open the thread of the last conversation
        Then I see a text "He creado la cuenta «Acme Labs» y su usuario."
        Then I see a text "Aprobado por"
        Then I see a text "recuperar contraseña"
        When I go to "/es/admin/accounts"
        Then I see a text "Acme Labs"
        When I go to "/es/admin/users"
        Then I see a text "ana@acme.test"
        When I go to "/es/admin/events"
        When I filter by "byAssistant" with "1"
        Then the response status should be 200
        Then I see a text "Asistente IA</span>"
        Then I see a text "Registrada una nueva cuenta Acme Labs en estado"
        Then I see a text "Registrado el usuario ana@acme.test para la cuenta"
        Then I do not see a text "Plan aprobado por"

    Scenario: «Descartar» discards the plan
        When I discard the last plan
        Then the response status should be 204
        Then the plan of the last conversation has status "discarded"
        When I go to "/es/admin/accounts"
        Then I do not see a text "Acme Labs"
