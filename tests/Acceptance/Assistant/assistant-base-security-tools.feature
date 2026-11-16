Feature:
    The AI assistant reads the users, accounts and audit of Hydra

    Scenario: The assistant lists the users of an account
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-000000000001" exists
        Given user "ana@acme.test" with role "ROLE_OWNER" of account "0190f3a4-1b2c-7d3e-8f40-000000000001" exists
        Given the assistant will call the tool "list_users" with:
            """
            {"accountId": "0190f3a4-1b2c-7d3e-8f40-000000000001"}
            """
        Given the assistant will answer with text "Acme tiene un usuario."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué usuarios tiene Acme?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "ana@acme.test"
        When I open the thread of the last conversation
        Then I see a text "Acme tiene un usuario."
        Then I see a text "1 usuarios encontrados"

    Scenario: The assistant views the detail of an account
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-000000000001" exists
        Given the assistant will call the tool "view_account" with:
            """
            {"accountId": "0190f3a4-1b2c-7d3e-8f40-000000000001"}
            """
        Given the assistant will answer with text "Acme está activa."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Cómo está la cuenta Acme?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "apiTokensEnabled"
        When I open the thread of the last conversation
        Then I see a text "Acme está activa."
        Then I see a text "Cuenta «Acme»"

    Scenario: The assistant lists the recorded events without their content
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user with id "0190f3a4-1b2c-7d3e-8f40-0000000000f1" has been removed by the assistant execution "0190f3a4-1b2c-7d3e-8f40-0000000000e1"
        Given the assistant will call the tool "list_recorded_events" with:
            """
            {"byAssistant": true}
            """
        Given the assistant will answer with text "El asistente eliminó un usuario."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué ha hecho el asistente?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "userRemoved"
        Then the last assistant request does not contain the text "payload"
        When I open the thread of the last conversation
        Then I see a text "El asistente eliminó un usuario."
        Then I see a text "1 eventos encontrados"

    Scenario: An admin cannot use the sent emails and a super admin can
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given the assistant will call the tool "list_email_logs" with:
            """
            {}
            """
        Given the assistant will answer with text "No puedo ver los emails."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué emails se han enviado?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "La herramienta «list_email_logs» no está disponible"
        Given the assistant will call the tool "list_email_logs" with:
            """
            {}
            """
        Given the assistant will answer with text "No hay emails."
        When I am logged as "super@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué emails se han enviado?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text '"emails":[]'
        Then the last assistant request does not contain the text "no está disponible"

    Scenario: The account created by a plan is shown as done by the assistant in the activity log
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the assistant will call the tool "propose_plan" with:
            """
            {"summary": "Crear la cuenta Acme Labs", "steps": [{"tool": "register_account", "arguments": [{"name": "name", "value": "Acme Labs", "fromStep": null}, {"name": "active", "value": "true", "fromStep": null}]}]}
            """
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:Crea la cuenta Acme Labs" and button "Enviar"
        When the assistant processes its pending work
        Given the assistant will call the tool "register_account" with:
            """
            {"name": "Acme Labs", "active": true}
            """
        Given the assistant will answer with text "Cuenta creada."
        When I execute the last plan
        When the assistant processes its pending work
        Then the plan of the last conversation has status "executed"
        When I go to "/es/admin/events"
        Then the response status should be 200
        Then I see a text "Registrada una nueva cuenta Acme Labs en estado"
        Then I see a text "Asistente IA</span>"
