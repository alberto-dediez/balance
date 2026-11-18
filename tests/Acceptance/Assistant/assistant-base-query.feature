Feature:
    The AI assistant answers questions reading the data of Hydra

    Scenario: A question about the active accounts is answered listing the accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-000000000001" exists
        Given inactive account "Globex" with id "0190f3a4-1b2c-7d3e-8f40-000000000002" exists
        Given the assistant will call the tool "list_accounts" with:
            """
            {"active": true}
            """
        Given the assistant will answer with text "Hay una cuenta activa: Acme."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué cuentas hay activas?" and button "Enviar"
        Then the response status should be 200
        When the assistant processes its pending work
        Then the last assistant request contains the text "Acme"
        Then the last assistant request does not contain the text "Globex"
        When I open the thread of the last conversation
        Then the response status should be 200
        Then I see a text "Hay una cuenta activa: Acme."
        Then I see a text "Consultas realizadas (1)"
        Then I see a text "1 cuentas encontradas"

    Scenario: A question about a news entry is answered reading the changelog
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "usuarios" titled "Alta de usuarios" in category "feature" exists
        Given the assistant will call the tool "list_changelog_entries" with:
            """
            {"text": "usuarios"}
            """
        Given the assistant will call the tool "view_changelog_entry" with:
            """
            {"slug": "usuarios"}
            """
        Given the assistant will answer with text "Se explica en la novedad «Alta de usuarios»."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Cómo doy de alta un usuario?" and button "Enviar"
        When the assistant processes its pending work
        When I open the thread of the last conversation
        Then I see a text "Se explica en la novedad «Alta de usuarios»."
        Then I see a text "Consultas realizadas (2)"

    Scenario: The empty panel suggests questions about the data of Hydra
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        Then the response status should be 200
        Then I see a text "¿Qué cuentas están activas?"
        Then I see a text "¿Cuál es la última novedad publicada?"
        Then I do not see a text "¿Qué tareas tiene abiertas este proyecto?"
        Then I do not see a text "NEW_"
