Feature:
    Ability for admins to mark a contact request as gestionada

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given contact request with id "CR-1" of account "666" exists

    Scenario: As an admin I can mark a pending contact request as gestionada
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests/CR-1/manage" with csrf token "contact_request_manage"
        Then the only contact request should be "managed"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud marcada como gestionada"
        Then I do not see a text "CR-1@example.com"

    Scenario: As an admin I see a warning when the contact request was already resolved
        Given contact request "CR-1" is managed
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests/CR-1/manage" with csrf token "contact_request_manage"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La solicitud ya estaba resuelta"
        Then the only contact request should be "managed"

    Scenario: As an owner I can't mark a contact request as gestionada
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/contact-requests/CR-1/manage" with csrf token "contact_request_manage"
        Then the response status should be 403
        Then the only contact request should be "pending"
