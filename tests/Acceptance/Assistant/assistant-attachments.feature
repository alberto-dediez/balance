Feature:
    The admin attaches files to the messages of the AI assistant

    Background:
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "other@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"

    Scenario: A message with a PDF shows the attachment and the AI receives the document
        Given the assistant will answer with text "El documento trata de DNS."
        When I send the assistant message "Resume el documento" attaching "informe.pdf"
        Then the response status should be 200
        Then the last conversation has 2 messages
        Then the last conversation has 1 stored attachment files
        When the assistant processes its pending work
        Then the last assistant request contains the document "informe.pdf"
        When I open the thread of the last conversation
        Then I see a text "informe.pdf"
        Then I see a text "El documento trata de DNS."

    Scenario: The document is sent again in later messages of the conversation
        Given the assistant will answer with text "Primera respuesta"
        Given the assistant will answer with text "Segunda respuesta"
        When I send the assistant message "Resume el documento" attaching "informe.pdf"
        When the assistant processes its pending work
        When I open the panel of the last conversation
        When I submit a form with "text:¿Y el segundo apartado?" and button "Enviar"
        When the assistant processes its pending work
        Then the last assistant request contains the document "informe.pdf"
        Then the last assistant request contains the text "¿Y el segundo apartado?"

    Scenario: A text file is sent as text
        Given the assistant will answer with text "Leído"
        When I send the assistant message "Lee las notas" attaching "notas.txt"
        When the assistant processes its pending work
        Then the last assistant request contains the text "contents of notas.txt"

    Scenario: Five files are accepted
        When I send the assistant message "Mira esto" attaching "a.pdf,b.pdf,c.txt,d.png,e.md"
        Then the response status should be 200
        Then the last conversation has 5 stored attachment files

    Scenario: A sixth file is rejected and the message is not sent
        When I send the assistant message "Mira esto" attaching "a.pdf,b.pdf,c.txt,d.png,e.md,f.csv"
        Then the response status should be 422
        Then user "admin@dediez.software" has 0 assistant conversations
        Then the assistant has 0 stored attachment files

    Scenario: A file of an unsupported type is rejected and the message is not sent
        When I send the assistant message "Mira esto" attaching "programa.zip"
        Then the response status should be 422
        Then user "admin@dediez.software" has 0 assistant conversations
        Then the assistant has 0 stored attachment files

    Scenario: A file larger than the limit is rejected and the message is not sent
        When I send the assistant message "Mira esto" attaching the file "enorme.pdf" of 10485761 bytes
        Then the response status should be 422
        Then user "admin@dediez.software" has 0 assistant conversations
        Then the assistant has 0 stored attachment files

    Scenario: The owner downloads its attachment
        When I send the assistant message "Resume el documento" attaching "informe.pdf"
        When I download the first attachment of the last conversation
        Then the response status should be 200
        Then I see a text "%PDF-1.4"

    Scenario: Another admin cannot download the attachment
        When I send the assistant message "Resume el documento" attaching "informe.pdf"
        When I am logged as "other@dediez.software"
        When I download the first attachment of the last conversation
        Then the response status should be 404

    Scenario: Removing the conversation removes its files
        Given the assistant will answer with text "Listo"
        When I send the assistant message "Resume el documento" attaching "informe.pdf"
        When the assistant processes its pending work
        Then the assistant has 1 stored attachment files
        When I remove the assistant conversation
        Then the response status should be 204
        Then the assistant has 0 stored attachment files
        Then user "admin@dediez.software" has 0 assistant conversations
