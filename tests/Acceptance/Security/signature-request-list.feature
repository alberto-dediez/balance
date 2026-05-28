Feature:
    Ability to list signature requests in web

    Scenario: As admin I can access a list of all signature requests
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given signature request with id "1111" and account "account-1" exists
        Given signature request with id "2222" and account "account-1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all signature requests
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given signature request with id "1111" and account "account-1" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/signature-requests"
        Then the response status should be 403
