Feature:
    Ability to manage the announcements as admin

    Scenario: As admin I see drafts, scheduled and published announcements
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Publicada uno" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "Borrador uno" with id "22222222-2222-2222-2222-222222222222" exists
        Given announcement "Programada uno" with id "33333333-3333-3333-3333-333333333333" is scheduled for "2030-01-01 10:00"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements"
        Then the response status should be 200
        Then I see a table with 5 rows
        Then I see a text "Publicada uno"
        Then I see a text "Borrador uno"
        Then I see a text "Programada uno"
        Then I see a text "Publicada"
        Then I see a text "Borrador"
        Then I see a text "01/01/2030 10:00"
        Then I see a link with name "Registrar"

    Scenario: As admin I filter the announcements by status
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Publicada uno" with id "11111111-1111-1111-1111-111111111111" exists
        Given announcement "Borrador uno" with id "22222222-2222-2222-2222-222222222222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements"
        When I filter by "status" with "draft"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "Borrador uno"
        Then I do not see a text "Publicada uno"

    Scenario: As owner I cannot manage the announcements
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/announcements"
        Then the response status should be 403

    Scenario: As user I cannot manage the announcements
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/announcements"
        Then the response status should be 403
