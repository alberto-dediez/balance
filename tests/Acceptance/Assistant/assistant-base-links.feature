Feature:
    The AI assistant links the elements it mentions to their screens

    Scenario: An account mentioned with its reference is a link to its screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-000000000001" exists
        Given the assistant will call the tool "list_accounts" with:
            """
            {"active": true}
            """
        Given the assistant will answer with text "Hay una cuenta activa: [Acme](route:admin_accounts_edit?accountId=0190f3a4-1b2c-7d3e-8f40-000000000001)."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Qué cuentas hay activas?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "route:admin_accounts_edit?accountId=0190f3a4-1b2c-7d3e-8f40-000000000001"
        When I open the thread of the last conversation
        Then the response status should be 200
        Then I see a link "Acme" to "/es/admin/accounts/edit/0190f3a4-1b2c-7d3e-8f40-000000000001"

    Scenario: A reference to a route that does not exist is plain text
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the assistant will answer with text "Mira [Nada](route:no_existe) aquí."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Dónde está?" and button "Enviar"
        When the assistant processes its pending work
        When I open the thread of the last conversation
        Then the response status should be 200
        Then I see a text "Mira Nada aquí."
        Then I do not see a link with name "Nada"

    Scenario: An internal address written by the assistant is plain text
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the assistant will answer with text "Puedes [Borrar](/es/admin/users/remove/0190f3a4-1b2c-7d3e-8f40-000000000001?_token=x) el usuario."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Cómo lo borro?" and button "Enviar"
        When the assistant processes its pending work
        When I open the thread of the last conversation
        Then the response status should be 200
        Then I see a text "Puedes Borrar el usuario."
        Then I do not see a link with name "Borrar"

    Scenario: A question about where a screen is links the screen of the menu
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the assistant will call the tool "list_screens" with:
            """
            {"term": "usuarios"}
            """
        Given the assistant will answer with text "Están en [Usuarios](route:admin_users)."
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:¿Dónde veo los usuarios?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the text "route:admin_users"
        When I open the thread of the last conversation
        Then the response status should be 200
        Then I see a link "Usuarios" to "/es/admin/users"
