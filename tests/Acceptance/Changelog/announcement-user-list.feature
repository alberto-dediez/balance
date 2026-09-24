Feature:
    Ability to list the published announcements as any user

    Scenario: As user I only see the published announcements
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Publicada uno" with id "11111111-1111-1111-1111-111111111111" exists
        Given published announcement "Publicada dos" with id "22222222-2222-2222-2222-222222222222" exists
        Given announcement "Borrador X" with id "33333333-3333-3333-3333-333333333333" exists
        Given announcement "Programada X" with id "44444444-4444-4444-4444-444444444444" is scheduled for "2030-01-01 10:00"
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "Publicada uno"
        Then I see a text "Publicada dos"
        Then I do not see a text "Borrador X"
        Then I do not see a text "Programada X"

    Scenario: As owner I only see the published announcements
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        Given published announcement "Publicada uno" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "Borrador X" with id "33333333-3333-3333-3333-333333333333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/announcements"
        Then the response status should be 200
        Then I see a table with 2 rows
        Then I do not see a text "Borrador X"

    Scenario: As admin the user list also shows only the published announcements
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Publicada uno" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "Borrador X" with id "33333333-3333-3333-3333-333333333333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/announcements"
        Then the response status should be 200
        Then I see a table with 2 rows
        Then I do not see a text "Borrador X"

    Scenario: As user I filter the announcements by category
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Publicada uno" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/announcements"
        When I filter by "category" with "fix"
        Then the response status should be 200
        Then I do not see a text "Publicada uno"
        Then I see a text "No hay novedades publicadas"

    Scenario: The list shows the translation of the current locale and falls back to the default one
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Traducida" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "11111111-1111-1111-1111-111111111111" is translated to "en" as "Translated"
        Given published announcement "Solo en español" with id "22222222-2222-2222-2222-222222222222" exists
        When I am logged as "user@dediez.software"
        When I go to "/en/announcements"
        Then the response status should be 200
        Then I see a text "Translated"
        Then I see a text "Solo en español"

    Scenario: The menu of a user contains the announcements link
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "/es/announcements"
