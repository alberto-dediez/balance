Feature:
    Ability to remove announcements as admin

    Scenario: As admin I remove an announcement
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/remove/11111111-1111-1111-1111-111111111111" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la novedad Mi novedad"
        Then I do not see a text "/es/admin/announcements/edit/11111111-1111-1111-1111-111111111111"

    Scenario: Removing without csrf token is forbidden
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/announcements/remove/11111111-1111-1111-1111-111111111111"
        Then the response status should be 403

    Scenario: As owner I cannot remove announcements
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        Given published announcement "Mi novedad" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/announcements/remove/11111111-1111-1111-1111-111111111111" with csrf token "remove"
        Then the response status should be 403
