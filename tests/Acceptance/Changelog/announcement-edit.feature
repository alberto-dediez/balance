Feature:
    Ability to edit announcements as admin

    Scenario: As admin I edit the title of an announcement
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Título antiguo" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/edit/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        When I submit a form with "title:Título nuevo" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Novedad actualizada con éxito"
        Then I see a text "Título nuevo"
        Then I do not see a text "Título antiguo"

    Scenario: As admin I translate an announcement browsing in english
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Título en español" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/en/admin/announcements/edit/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        When I submit a form with "title:English title" and button "Save and exit"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "English title"
        When I go to "/es/admin/announcements"
        Then I see a text "Título en español"
        Then I do not see a text "English title"
        Then I see a text "ES, EN"

    Scenario: Removing the manual from the picker
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Con manual" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "11111111-1111-1111-1111-111111111111" has a manual in "es"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/edit/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        When I submit a form with "manualId:" and button "Guardar"
        When I go to "/es/announcements/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I do not see a text "Ver manual"

    Scenario: An unknown announcement returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/edit/99999999-9999-9999-9999-999999999999"
        Then the response status should be 404

    Scenario: As user I cannot edit announcements
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given announcement "Título" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/announcements/edit/11111111-1111-1111-1111-111111111111"
        Then the response status should be 403
