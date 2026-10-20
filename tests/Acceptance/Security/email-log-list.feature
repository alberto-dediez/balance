Feature:
    I want to make sure that only super admins can list the sent emails

    Scenario: As super admin I can list the sent emails
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000002" to "second@dediez.software" exists with status "Delivered"
        When I am logged as "super@dediez.software"
        When I go to "/es/admin/emails"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "first@dediez.software"
        Then I see a text "second@dediez.software"

    Scenario: As super admin I can filter the sent emails by status
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000001" to "first@dediez.software" exists
        Given email log with id "0199f3b2-0000-7000-8000-000000000002" to "second@dediez.software" exists with status "Delivered"
        When I am logged as "super@dediez.software"
        When I go to "/es/admin/emails"
        When I filter by "status" with "Delivered"
        Then the response status should be 200
        Then I see a table with 2 rows
        Then I see a text "second@dediez.software"
        Then I do not see a text "first@dediez.software"
        Then I see a text "<b>Entregado</b>"
        Then I do not see a text "<b>Delivered</b>"

    Scenario: As admin I can't list the sent emails
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emails"
        Then the response status should be 403

    Scenario: As user I can't list the sent emails
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/emails"
        Then the response status should be 403
