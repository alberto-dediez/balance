Feature:
    I want to make sure that the system is allow to remove a notification

    Scenario: As the recipient user I can remove my own notification
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/remove" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La notificación ha sido eliminada"

    Scenario: As a different user I cannot remove another user's notification
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user2@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/remove" with csrf token "remove"
        Then the response status should be 403

    Scenario: As an admin I can remove any notification
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/remove" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La notificación ha sido eliminada"
