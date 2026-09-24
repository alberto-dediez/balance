Feature:
    Ability to publish announcements as admin

    Scenario: As admin I publish an announcement without notifying
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/publish/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        When I submit a form with "publishAt:" and button "Publicar"
        Then no deferred command is queued in the low priority transport
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Novedad publicada"
        Then I see a text "Publicada"
        Then announcement "11111111-1111-1111-1111-111111111111" is published

    Scenario: As admin I publish an announcement notifying the users
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/publish/11111111-1111-1111-1111-111111111111"
        When I submit a form with "notifyUsers:1" and button "Publicar"
        Then a deferred "NotifyAnnouncementRequest" command is queued in the low priority transport
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se está notificando a los usuarios"
        Then announcement "11111111-1111-1111-1111-111111111111" is published

    Scenario: As admin I schedule the publication of an announcement
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/publish/11111111-1111-1111-1111-111111111111"
        When I schedule the publication for "2030-01-01T10:00" with notification
        Then no deferred command is queued in the low priority transport
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Novedad programada para el 01/01/2030 10:00"
        Then I see a text "Programada"
        Then announcement "11111111-1111-1111-1111-111111111111" is not published

    Scenario: As admin I schedule the publication of an announcement in my browser timezone
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When my browser timezone is "Europe/Madrid"
        When I go to "/es/admin/announcements/publish/11111111-1111-1111-1111-111111111111"
        When I schedule the publication for "2030-07-01T12:00" with notification
        Then announcement "11111111-1111-1111-1111-111111111111" is scheduled for "2030-07-01 10:00" UTC
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Novedad programada para el 01/07/2030 12:00"
        When I go to "/es/admin/announcements/publish/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I see a text "01/07/2030 12:00"
        Then I see "2030-07-01T12:00" in the publication date field

    Scenario: An already published announcement is not published again
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/publish/11111111-1111-1111-1111-111111111111"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La novedad ya estaba publicada"

    Scenario: As user I cannot publish announcements
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/announcements/publish/11111111-1111-1111-1111-111111111111"
        Then the response status should be 403
