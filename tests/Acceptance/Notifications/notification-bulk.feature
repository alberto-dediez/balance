Feature:
    I want to make sure that the system allows to manage my notifications in bulk

    Scenario: As a user I can mark several notifications as read at once
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" exists
        Given notification with id "33333333-3333-3333-3333-333333333333" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I submit a form with "elements:[33333333-3333-3333-3333-333333333333,22222222-2222-2222-2222-222222222222]|action:mark-as-read" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha marcado como leída la notificación"
        Then notification with id "33333333-3333-3333-3333-333333333333" is read
        Then notification with id "22222222-2222-2222-2222-222222222222" is read
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read
        Then I see a text "Marcar todas como leídas"

    Scenario: Marking all my unread notifications in bulk hides the mark-all button
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I submit a form with "elements:[11111111-1111-1111-1111-111111111111]|action:mark-as-read" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha marcado como leída la notificación «Asunto»"
        Then notification with id "11111111-1111-1111-1111-111111111111" is read
        Then I do not see a text "Marcar todas como leídas"

    Scenario: As a user I can remove several notifications at once
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" exists
        Given notification with id "33333333-3333-3333-3333-333333333333" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I submit a form with "elements:[33333333-3333-3333-3333-333333333333,22222222-2222-2222-2222-222222222222]|action:remove" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la notificación «Asunto»"
        When I go to "/es/notifications"
        Then I see a table with 3 rows
        Then notification with id "33333333-3333-3333-3333-333333333333" does not exist
        Then notification with id "22222222-2222-2222-2222-222222222222" does not exist
        Then notification with id "11111111-1111-1111-1111-111111111111" exists

    Scenario: Removing all my unread notifications in bulk empties the notifications widget
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications/widget"
        Then I see a text "Asunto"
        When I go to "/es/notifications"
        When I submit a form with "elements:[22222222-2222-2222-2222-222222222222,11111111-1111-1111-1111-111111111111]|action:remove" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        When I go to "/es/notifications/widget"
        Then the response status should be 200
        Then I see a text "No tienes notificaciones sin leer"
        Then I do not see a text "Asunto"

    Scenario: Executing a bulk action without selecting notifications shows an error
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I submit a form with "action:remove" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Debes seleccionar algún elemento sobre el que poder realizar la acción"
        Then notification with id "11111111-1111-1111-1111-111111111111" exists

    Scenario: I cannot act on another user's notification through the bulk form
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user2@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I send the bulk form with elements "22222222-2222-2222-2222-222222222222" and action "remove"
        Then the response status should be 302
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La selección ha cambiado; revisa los elementos y vuelve a intentarlo"
        Then notification with id "22222222-2222-2222-2222-222222222222" exists
        Then notification with id "22222222-2222-2222-2222-222222222222" is not read

    Scenario: The direct bulk form submission processes one of my notifications
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I send the bulk form with elements "11111111-1111-1111-1111-111111111111" and action "remove"
        Then the response status should be 302
        Then notification with id "11111111-1111-1111-1111-111111111111" does not exist

    Scenario: A bulk action with a notification that is no longer in the page shows an error
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I send the bulk form with elements "11111111-1111-1111-1111-111111111111,44444444-4444-4444-4444-444444444444" and action "remove"
        Then the response status should be 302
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La selección ha cambiado; revisa los elementos y vuelve a intentarlo"
        Then notification with id "11111111-1111-1111-1111-111111111111" exists

    Scenario: A bulk action with an invalid action shows a generic error
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I send the bulk form with elements "11111111-1111-1111-1111-111111111111" and action "foo"
        Then the response status should be 302
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No se ha podido realizar la acción; revisa el formulario y vuelve a intentarlo"
        Then I do not see a text "La selección ha cambiado; revisa los elementos y vuelve a intentarlo"
        Then notification with id "11111111-1111-1111-1111-111111111111" exists

    Scenario: Marking an already read notification again is accepted
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" is read
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I submit a form with "elements:[11111111-1111-1111-1111-111111111111]|action:mark-as-read" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha marcado como leída la notificación «Asunto»"
        Then notification with id "11111111-1111-1111-1111-111111111111" is read

    Scenario: As a user I can mark several notifications as unread at once
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" is read
        Given notification with id "22222222-2222-2222-2222-222222222222" for user "user1@dediez.software" is read
        Given notification with id "33333333-3333-3333-3333-333333333333" for user "user1@dediez.software" is read
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I submit a form with "elements:[33333333-3333-3333-3333-333333333333,22222222-2222-2222-2222-222222222222]|action:mark-as-unread" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha marcado como no leída la notificación «Asunto»"
        Then notification with id "33333333-3333-3333-3333-333333333333" is not read
        Then notification with id "22222222-2222-2222-2222-222222222222" is not read
        Then notification with id "11111111-1111-1111-1111-111111111111" is read
        Then I see a text "Marcar todas como leídas"

    Scenario: Marking an already unread notification as unread in bulk is accepted
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given notification with id "11111111-1111-1111-1111-111111111111" for user "user1@dediez.software" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/notifications"
        When I submit a form with "elements:[11111111-1111-1111-1111-111111111111]|action:mark-as-unread" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha marcado como no leída la notificación «Asunto»"
        Then notification with id "11111111-1111-1111-1111-111111111111" is not read
