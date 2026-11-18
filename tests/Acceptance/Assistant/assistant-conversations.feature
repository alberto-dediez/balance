Feature:
    The AI assistant keeps the conversations of each admin

    Scenario: A new conversation starts empty
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        Then the response status should be 200
        Then I see a text "Nueva conversación"
        Then I see a text "Pregunta lo que necesites"

    Scenario: The admin lists only its own previous conversations
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "other@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Tareas de Acme" of user "admin@dediez.software" exists
        Given conversation "Facturas del año" of user "admin@dediez.software" exists
        Given conversation "Conversación ajena" of user "other@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/conversations"
        Then the response status should be 200
        Then I see a text "Tareas de Acme"
        Then I see a text "Facturas del año"
        Then I do not see a text "Conversación ajena"

    Scenario: The admin reopens a previous conversation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Tareas de Acme" with id "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "admin@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel?conversationId=0190f3a4-1b2c-7d3e-8f40-0000000000c1"
        Then the response status should be 200
        Then I see 2 times the text "Tareas de Acme"
        When I go to "/es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/status"
        Then the response status should be 200

    Scenario: Without a conversation identifier a new message starts a new conversation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Tareas de Acme" of user "admin@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:Hola" and button "Enviar"
        Then the response status should be 200
        Then user "admin@dediez.software" has 2 assistant conversations

    Scenario: The admin renames a conversation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Tareas de Acme" of user "admin@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I rename the assistant conversation to "Migración DNS"
        Then the response status should be 204
        Then the last conversation is titled "Migración DNS"
        When I go to "/es/assistant/conversations"
        Then I see a text "Migración DNS"
        Then I do not see a text "Tareas de Acme"

    Scenario: An empty title is rejected
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Tareas de Acme" of user "admin@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I rename the assistant conversation to ""
        Then the response status should be 422
        Then the last conversation is titled "Tareas de Acme"

    Scenario: The admin removes a conversation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Tareas de Acme" of user "admin@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I remove the assistant conversation
        Then the response status should be 204
        Then user "admin@dediez.software" has 0 assistant conversations

    Scenario: A conversation with work in progress cannot be removed
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Tareas de Acme" of user "admin@dediez.software" exists
        Given the last conversation has a pending answer
        When I am logged as "admin@dediez.software"
        When I remove the assistant conversation
        Then the response status should be 422
        Then user "admin@dediez.software" has 1 assistant conversations
