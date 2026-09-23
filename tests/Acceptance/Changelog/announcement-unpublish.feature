Feature:
    Ability to unpublish announcements as admin

    Scenario: As admin I unpublish a published announcement
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/unpublish/11111111-1111-1111-1111-111111111111" with csrf token "unpublish"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Novedad despublicada"
        Then I see a text "Borrador"
        Then announcement "11111111-1111-1111-1111-111111111111" is not published

    Scenario: As admin I cancel a scheduled publication
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" is scheduled for "2030-01-01 10:00"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/unpublish/11111111-1111-1111-1111-111111111111" with csrf token "unpublish"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Programación cancelada"
        Then I see a text "Borrador"

    Scenario: Unpublishing without csrf token is forbidden
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/unpublish/11111111-1111-1111-1111-111111111111"
        Then the response status should be 403

    Scenario: As user I cannot unpublish announcements
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/announcements/unpublish/11111111-1111-1111-1111-111111111111" with csrf token "unpublish"
        Then the response status should be 403
