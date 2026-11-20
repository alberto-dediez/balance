Feature:
    I want to make sure that the system is allow to refresh my notifications widget

    Scenario: As a user I can fetch my notifications widget
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/widget"
        Then the response status should be 200
        Then I see a text "Asunto"
        Then I see a text "Marcar todas como leídas"

    Scenario: The widget is empty when there is nothing unread
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/widget"
        Then the response status should be 200
        Then I see a text "No tienes notificaciones sin leer"
        Then I do not see a text "Marcar todas como leídas"

    Scenario: Anonymous users cannot fetch the widget
        When I go to "/es/notifications/widget"
        Then the response status should be 403

    Scenario: Marking a notification as unread brings it back to the widget
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" is read
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/widget"
        Then I see a text "No tienes notificaciones sin leer"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/unread" with csrf token "mark_as_unread"
        When I go to "/es/notifications/widget"
        Then I see a text "Asunto"

    Scenario: The widget offers to mark each unread notification as read
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/widget"
        Then I see a link with title "Marcar como leída"

    Scenario: The empty widget does not offer to mark as read
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/widget"
        Then I do not see a link with title "Marcar como leída"

    Scenario: As a user I can mark a notification as read from the widget
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I request "/es/notifications/11111111-1111-1111-1111-111111111111/read" by ajax with csrf token "mark_as_read"
        Then the response status should be 204
        Then notification with id "11111111-1111-1111-1111-111111111111" is read
        When I go to "/es/notifications/widget"
        Then I see a text "No tienes notificaciones sin leer"
        Then I do not see a text "Marcar todas como leídas"

    Scenario: Marking one notification from the widget keeps the others
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I request "/es/notifications/11111111-1111-1111-1111-111111111111/read" by ajax with csrf token "mark_as_read"
        Then the response status should be 204
        Then notification with id "11111111-1111-1111-1111-111111111111" is read
        Then notification with id "22222222-2222-2222-2222-222222222222" is not read
        When I go to "/es/notifications/widget"
        Then I see a link with title "Marcar como leída"
        Then I see a text "Marcar todas como leídas"

    Scenario: Marking from the widget without csrf token is forbidden
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/11111111-1111-1111-1111-111111111111/read" as an ajax request
        Then the response status should be 403
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read

    Scenario: As a different user I cannot mark another user's notification from the widget
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user2@dediez.software"
        When I request "/es/notifications/11111111-1111-1111-1111-111111111111/read" by ajax with csrf token "mark_as_read"
        Then the response status should be 403
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read

    Scenario: Marking an unknown notification from the widget returns not found
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I request "/es/notifications/99999999-9999-9999-9999-999999999999/read" by ajax with csrf token "mark_as_read"
        Then the response status should be 404
