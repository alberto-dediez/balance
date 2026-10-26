Feature:
    I want to make sure that only super admins can see the detail of a sent email

    Scenario: As super admin I can see the detail of a sent email
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I am logged as "super@dediez.software"
        When I go to "/es/admin/emails/0199f3b2-0000-7000-8000-000000000001"
        Then the response status should be 200
        Then I see a text "first@dediez.software"
        Then I see a text "Enviado"
        Then I see a text "Ver auditoría"
        Then I do not see a text "Cuerpo"

    Scenario: As admin I can't see the detail of a sent email
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emails/0199f3b2-0000-7000-8000-000000000001"
        Then the response status should be 403

    Scenario: As super admin I get a not found for an unknown email
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        When I am logged as "super@dediez.software"
        When I go to "/es/admin/emails/0199f3b2-0000-7000-8000-000000000099"
        Then the response status should be 404

    Scenario: As super admin I can see the history of a sent email
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I send a "POST" request to "/webhook/sendgrid" with body:
            """
            [{"email":"first@dediez.software","timestamp":1700000000,"event":"delivered","sg_event_id":"e1","sg_message_id":"m1","email_log_id":"0199f3b2-0000-7000-8000-000000000001"}]
            """
        When I am logged as "super@dediez.software"
        When I go to "/es/admin/emails/0199f3b2-0000-7000-8000-000000000001/history"
        Then the response status should be 200
        Then I see a text "Histórico"
        Then I see a text "Email entregado el"

    Scenario: As admin I can't see the history of a sent email
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emails/0199f3b2-0000-7000-8000-000000000001/history"
        Then the response status should be 403

    Scenario: As super admin I get a not found for the history of an unknown email
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        When I am logged as "super@dediez.software"
        When I go to "/es/admin/emails/0199f3b2-0000-7000-8000-000000000099/history"
        Then the response status should be 404
