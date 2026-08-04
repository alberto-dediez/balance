Feature:
    I want to make sure that the system is allow to list and read my own notifications

    Scenario: As a user I can list my notifications
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        Then the response status should be 200
        Then I see a table with 2 rows
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
        When I send a "POST" request to "/es/notifications/read-all"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Todas las notificaciones se han marcado como leídas"
        Then I do not see a text "Marcar todas como leídas"
