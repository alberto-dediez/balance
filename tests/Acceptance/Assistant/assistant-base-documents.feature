Feature:
    The AI assistant reads the documents of the library and the changelog of Hydra

    Background:
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists

    Scenario: The assistant opens a PDF of the library
        Given document "especificacion.pdf" with id "0190f3a4-1b2c-7d3e-8f40-0000000000d1" exists
        Given the assistant will call the tool "read_media" with:
            """
            {"mediaId": "0190f3a4-1b2c-7d3e-8f40-0000000000d1"}
            """
        Given the assistant will answer with text "La especificación pide bajar el TTL."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué dice la especificación?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the document "especificacion.pdf"
        When I open the thread of the last conversation
        Then I see a text "La especificación pide bajar el TTL."
        Then I see a text "Consultas realizadas (1)"

    Scenario: The body of a news entry is found searching the changelog documents
        Given changelog entry "usuarios" titled "Alta de usuarios" in category "feature" exists
        Given the assistant will call the tool "search_changelog_documents" with:
            """
            {"text": "Contenido de prueba"}
            """
        Given the assistant will answer with text "Está en la novedad."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Dónde se habla del contenido?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text '"source":"entry"'
        Then the last assistant request contains the text "usuarios"
