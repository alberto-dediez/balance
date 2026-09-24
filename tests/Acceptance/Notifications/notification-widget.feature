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
