Feature:
    A failing step of a plan over the accounts of Hydra stops its execution

    Scenario: Editing an account removed after the proposal fails and the next step is skipped
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-000000000001" exists
        Given the assistant will call the tool "propose_plan" with:
            """
            {
                "summary": "Renombrar la cuenta Acme y crear la cuenta Globex",
                "steps": [
                    {"tool": "update_account", "arguments": [
                        {"name": "accountId", "value": "0190f3a4-1b2c-7d3e-8f40-000000000001", "fromStep": null},
                        {"name": "name", "value": "Acme SL", "fromStep": null}
                    ]},
                    {"tool": "register_account", "arguments": [
                        {"name": "name", "value": "Globex", "fromStep": null},
                        {"name": "active", "value": "true", "fromStep": null}
                    ]}
                ]
            }
            """
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:Renombra Acme y crea Globex" and button "Enviar"
        When the assistant processes its pending work
        Then the plan of the last conversation has status "proposed"
        When I go to "/es/admin/accounts/remove/0190f3a4-1b2c-7d3e-8f40-000000000001" with csrf token "remove"
        Given the assistant will call the tool "update_account" with:
            """
            {"accountId": "0190f3a4-1b2c-7d3e-8f40-000000000001", "name": "Acme SL"}
            """
        Given the assistant will call the tool "register_account" with:
            """
            {"name": "Globex", "active": true}
            """
        When I execute the last plan
        When the assistant processes its pending work
        Then the plan of the last conversation has status "failed"
        When I open the thread of the last conversation
        Then I see a text "Se han ejecutado 0 de 2 pasos. El paso 1 ha fallado"
        Then I see 1 times the text "Omitido"
        When I go to "/es/admin/accounts"
        Then I do not see a text "Globex"
        Then I do not see a text "Acme SL"
