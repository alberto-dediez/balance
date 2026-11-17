Feature:
    The AI assistant answers from the guide and shows the articles it read as sources

    Scenario: A guide article read by the assistant is shown as a source
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "admin" with title "Usuarios" and body:
            """
            Aquí se registran los usuarios de la plataforma.
            """
        Given the assistant will call the tool "read_guide_article" with:
            """
            {"path": "seguridad/organizacion/usuarios"}
            """
        Given the assistant will answer with text "Se hace en [Registrar usuarios](route:guide_article?path=seguridad%2Forganizacion%2Fusuarios)."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Cómo registro un usuario?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "Aquí se registran los usuarios de la plataforma."
        Then the last assistant request contains the text "route:guide_article?path=seguridad%2Forganizacion%2Fusuarios"
        When I open the thread of the last conversation
        Then the response status should be 200
        Then I see a text "Fuentes"
        Then I see a text "data-assistant-document"
        Then I see a link "Usuarios" to "/es/guide/seguridad/organizacion/usuarios"
        Then I see a link "Registrar usuarios" to "/es/guide/seguridad/organizacion/usuarios"

    Scenario: An article that only exists for another profile is not found
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "owner" with title "Usuarios de owner" and body:
            """
            Texto para propietarios.
            """
        Given the assistant will call the tool "read_guide_article" with:
            """
            {"path": "seguridad/organizacion/usuarios"}
            """
        Given the assistant will answer with text "No lo he encontrado."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Cómo registro un usuario?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "El artículo no existe"
        When I open the thread of the last conversation
        Then the response status should be 200
        Then I do not see a text "Fuentes"

    Scenario: A path that tries to leave the profile folder is rejected
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the assistant will call the tool "read_guide_article" with:
            """
            {"path": "../owner/usuarios"}
            """
        Given the assistant will answer with text "No puedo leerlo."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Cómo registro un usuario?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "no es una ruta de artículo válida"

    Scenario: Searching the guide only returns the articles of the user profile
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "admin" with title "Usuarios de admin" and body:
            """
            Se registra un usuario.
            """
        Given guide article "seguridad/organizacion/cuentas" for profile "owner" with title "Cuentas de owner" and body:
            """
            Se registra un usuario.
            """
        Given the assistant will call the tool "search_guide_articles" with:
            """
            {"text": "usuario"}
            """
        Given the assistant will answer with text "Hay un artículo."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Cómo registro un usuario?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "seguridad/organizacion/usuarios"
        Then the last assistant request does not contain the text "Cuentas de owner"
