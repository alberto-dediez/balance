Feature:
    I want to make sure that the system is allow to list and read my own notifications

    Scenario: As a user I can list my notifications
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "Marcar todas como leídas"

    Scenario: As a user I can read one of my notifications
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I see a text "Cuerpo"

    Scenario: As a user I can mark all my notifications as read
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I press "Marcar todas como leídas"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Todas las notificaciones se han marcado como leídas"
        Then I do not see a text "Marcar todas como leídas"

    Scenario: Marking all notifications as read without csrf token is forbidden
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I send a "POST" request to "/es/notifications/read-all"
        Then the response status should be 403
        When I go to "/es/notifications"
        Then I see a text "Marcar todas como leídas"

    Scenario: As a user I can mark one of my notifications as read from the list
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/read" with csrf token "mark_as_read"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La notificación se ha marcado como leída"
        Then notification with id "11111111-1111-1111-1111-111111111111" is read
        Then I do not see a text "Marcar todas como leídas"
        When I go to "/es/notifications"
        Then I see a link with title "Marcar como no leída"
        Then I do not see a link with title "Marcar como leída"

    Scenario: As a user I can mark one of my read notifications as unread from the list
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" is read
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/unread" with csrf token "mark_as_unread"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La notificación se ha marcado como no leída"
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read
        Then I see a text "Marcar todas como leídas"
        Then I see a text "Nueva"

    Scenario: The list shows the mark-as-unread action only for read notifications
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" is read
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        Then I see a link with title "Marcar como no leída"
        Then I see a link with title "Marcar como leída"

    Scenario: The list does not show the mark-as-unread action for unread notifications
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        Then I do not see a link with title "Marcar como no leída"

    Scenario: Marking a notification as read without csrf token is forbidden
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/read"
        Then the response status should be 403
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read

    Scenario: Marking a notification as unread without csrf token is forbidden
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" is read
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/unread"
        Then the response status should be 403
        Then notification with id "11111111-1111-1111-1111-111111111111" is read

    Scenario: As a different user I cannot change the read state of another user's notification
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user2@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/read" with csrf token "mark_as_read"
        Then the response status should be 403
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/unread" with csrf token "mark_as_unread"
        Then the response status should be 403

    Scenario: As an admin I cannot change the read state of another user's notification
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" is read
        When I am logged as "admin@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/read" with csrf token "mark_as_read"
        Then the response status should be 403
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read
        When I go to "/es/notifications/22222222-2222-2222-2222-222222222222/unread" with csrf token "mark_as_unread"
        Then the response status should be 403
        Then notification with id "22222222-2222-2222-2222-222222222222" is read

    Scenario: As an admin opening another user's notification detail does not mark it as read
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111"
        Then the response status should be 200
        Then I see a text "Cuerpo"
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read
        Then I do not see a link with name "Marcar como no leída"

    Scenario: As a user I can mark a notification as unread from its detail
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111"
        Then I see a link with name "Marcar como no leída"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/unread" with csrf token "mark_as_unread"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Mis notificaciones"
        Then I see a text "La notificación se ha marcado como no leída"
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read

    Scenario: Returning to the list after a row action keeps the current filter
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" is read
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I filter by "onlyUnread" with "1"
        Then I see a table with 3 rows
        When I go to "/es/notifications/22222222-2222-2222-2222-222222222222/read" with csrf token "mark_as_read"
        When I follow redirect
        When I go to "/es/notifications"
        Then I see a table with 2 rows

    Scenario: Acting on a notification that does not exist returns not found
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/99999999-9999-9999-9999-999999999999/read" with csrf token "mark_as_read"
        Then the response status should be 404
        When I go to "/es/notifications/99999999-9999-9999-9999-999999999999/unread" with csrf token "mark_as_unread"
        Then the response status should be 404
        When I go to "/es/notifications/99999999-9999-9999-9999-999999999999/remove" with csrf token "remove"
        Then the response status should be 404
        When I go to "/es/notifications/99999999-9999-9999-9999-999999999999"
        Then the response status should be 404
