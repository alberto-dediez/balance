Feature:
    I want to make sure that the software allow to register a signature request

    Scenario: As an admin, I can access to register new signature requests
        Given account "Test Account" with id "00000000-0000-0000-0000-000000000001" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "00000000-0000-0000-0000-000000000001" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new signature requests
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/signature-requests/register"
        Then the response status should be 403
