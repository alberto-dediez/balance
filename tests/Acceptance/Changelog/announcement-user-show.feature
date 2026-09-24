Feature:
    Ability to view an announcement as any user

    Scenario: As user I see a published announcement with its rendered content
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I see a text "<h1>Mi novedad</h1>"
        Then I do not see a text "Ver manual"
        Then I do not see a text "youtube-nocookie.com"
        Then I do not see a text "Esta novedad no está publicada"

    Scenario: An announcement with manual shows the manual links
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Con manual" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "11111111-1111-1111-1111-111111111111" has a manual in "es"
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I see a link with name "Ver manual"
        Then I see a link with name "Descargar manual"

    Scenario: An announcement with video embeds it
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Con vídeo" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "11111111-1111-1111-1111-111111111111" has the video "https://www.youtube.com/watch?v=dQw4w9WgXcQ" in "es"
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I see a text "youtube-nocookie.com/embed/dQw4w9WgXcQ"

    Scenario: As user I cannot see a draft
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given announcement "Borrador" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111"
        Then the response status should be 404

    Scenario: As user I cannot see a scheduled announcement
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given announcement "Programada" with id "11111111-1111-1111-1111-111111111111" is scheduled for "2030-01-01 10:00"
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111"
        Then the response status should be 404

    Scenario: As admin I preview a draft with a warning
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Borrador" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I see a text "Esta novedad no está publicada y solo la ven los administradores"

    Scenario: An unknown announcement returns 404
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/99999999-9999-9999-9999-999999999999"
        Then the response status should be 404

    Scenario: The manual of a published announcement is downloadable
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Con manual" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "11111111-1111-1111-1111-111111111111" has a manual in "es"
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111/manual"
        Then the response status should be 200
        Then the response content type is "application/pdf"

    Scenario: The manual of an announcement without manual returns 404
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Sin manual" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111/manual"
        Then the response status should be 404
