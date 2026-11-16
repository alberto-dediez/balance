Feature:
    The AI assistant remembers the queries of the previous turns in Hydra

    Scenario: The second request to the AI contains the queries of the first turn
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-000000000001" exists
        Given the assistant will call the tool "list_accounts" with:
            """
            {"name": "Acme"}
            """
        Given the assistant will answer with text "Hay una cuenta: Acme."
        Given the assistant will answer with text "Es la cuenta 0190f3a4-1b2c-7d3e-8f40-000000000001."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué cuentas se llaman Acme?" and button "Enviar"
        When the assistant processes its pending work
        When I open the panel of the last conversation
        When I submit a form with "text:¿Cuál es su id?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "list_accounts"
        Then the last assistant request contains the text "name=Acme"
        Then the last assistant request contains the text "0190f3a4-1b2c-7d3e-8f40-000000000001"
        Then the last assistant request contains the text "¿Cuál es su id?"
